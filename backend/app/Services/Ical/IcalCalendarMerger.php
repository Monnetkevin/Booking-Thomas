<?php

namespace App\Services\Ical;

use App\Enums\BlockType;
use App\Enums\IcalSyncStatus;
use App\Exceptions\Ical\IcalSyncException;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Availability\AvailabilityService;
use App\Services\Availability\BlockConflictService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Applique le contenu d'un flux aux blocs du logement, sous le verrou du logement :
 * UID nouveau → bloc créé ; dates modifiées → bloc déplacé ; UID disparu → bloc désactivé
 * (annulation chez la plateforme). Les séjours terminés ne sont plus touchés.
 * Un flux soudain vide alors que des nuits étaient bloquées est refusé : il ne libère rien.
 */
class IcalCalendarMerger
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BlockConflictService $conflicts,
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * @param  list<ImportedEvent>  $events
     *
     * @throws IcalSyncException flux vide suspect
     */
    public function merge(IcalFeed $feed, array $events): SyncResult
    {
        $today = CarbonImmutable::instance(Date::today());
        $upcoming = array_values(array_filter($events, fn (ImportedEvent $event) => $event->endsOn->gt($today)));

        $result = DB::transaction(function () use ($feed, $upcoming, $today): SyncResult {
            $this->availability->lockApartment($feed->apartment_id);

            $blocks = $this->availability->feedBlocks($feed->id)->keyBy('external_uid');
            $current = $blocks->filter(fn (AvailabilityBlock $block) => $block->is_active && $block->ends_on->gt($today));
            if ($upcoming === [] && $current->isNotEmpty()) {
                throw IcalSyncException::suspiciouslyEmpty();
            }

            // Annulations d'abord : leurs nuits peuvent accueillir un séjour déplacé.
            $seen = array_flip(array_map(fn (ImportedEvent $event) => $event->uid, $upcoming));
            $removed = 0;
            foreach ($current as $uid => $block) {
                if (! isset($seen[$uid])) {
                    $this->deactivate($block);
                    $removed++;
                }
            }

            $created = $updated = 0;
            foreach ($upcoming as $event) {
                $block = $blocks->get($event->uid);
                if ($block === null) {
                    $blocks->put($event->uid, $this->create($feed, $event));
                    $created++;
                } elseif ($this->apply($block, $event)) {
                    $updated++;
                }
            }

            $conflicts = $blocks->filter(
                fn (AvailabilityBlock $block) => $block->is_active && $block->has_conflict && $block->ends_on->gt($today),
            )->count();

            return new SyncResult(IcalSyncStatus::Success, $created, $updated, $removed, $conflicts);
        });

        if ($result->hasChanges()) {
            $this->availabilityService->flushApartment($feed->apartment_id);
        }

        return $result;
    }

    private function create(IcalFeed $feed, ImportedEvent $event): AvailabilityBlock
    {
        return $this->availability->createBlock([
            'apartment_id' => $feed->apartment_id,
            'type' => BlockType::Ical,
            'ical_feed_id' => $feed->id,
            'external_uid' => $event->uid,
            ...$this->placement($feed->apartment_id, $event),
            ...$this->details($event),
        ]);
    }

    /**
     * Met le bloc à jour ; renvoie true si ses nuits ont changé (déplacé ou réactivé).
     * Un bloc en conflit est réévalué à chaque passage : le conflit a pu disparaître.
     */
    private function apply(AvailabilityBlock $block, ImportedEvent $event): bool
    {
        $moved = ! $block->is_active
            || ! $block->starts_on->equalTo($event->startsOn)
            || ! $block->ends_on->equalTo($event->endsOn);

        $attributes = $this->details($event);
        if ($moved || $block->has_conflict) {
            $attributes += $this->placement($block->apartment_id, $event, $block->id);
        }
        $this->availability->updateBlock($block, $attributes);

        return $moved;
    }

    private function deactivate(AvailabilityBlock $block): void
    {
        $this->availability->updateBlock($block, ['is_active' => false, 'has_conflict' => false]);
    }

    /**
     * Nuits du bloc, actif, en conflit s'il recouvre des nuits déjà occupées.
     *
     * @return array<string, mixed>
     */
    private function placement(int $apartmentId, ImportedEvent $event, ?int $exceptBlockId = null): array
    {
        return [
            'starts_on' => $event->startsOn->toDateString(),
            'ends_on' => $event->endsOn->toDateString(),
            'is_active' => true,
            'has_conflict' => $this->conflicts->check($apartmentId, $event->startsOn, $event->endsOn, $exceptBlockId)->isConflict(),
        ];
    }

    /**
     * @return array{note: string|null, external_url: string|null}
     */
    private function details(ImportedEvent $event): array
    {
        return ['note' => $event->summary, 'external_url' => $event->url];
    }
}
