<?php

namespace App\Services\Ical;

use App\Enums\BlockType;
use App\Enums\IcalSyncStatus;
use App\Events\IcalConflictDetected;
use App\Events\StayScheduleChanged;
use App\Exceptions\Ical\EmptyCalendarRefusedException;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\IcalFeed;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Availability\AvailabilityService;
use App\Services\Availability\BlockConflictService;
use App\Services\Availability\BlockOverlap;
use App\Services\Booking\BookingService;
use App\Services\Housekeeping\StayChange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Applique le contenu d'un flux aux blocs du logement, sous le verrou du logement :
 * UID nouveau → bloc créé ; dates modifiées → bloc déplacé ; UID disparu → bloc désactivé
 * (annulation chez la plateforme). Les séjours terminés ne sont plus touchés.
 * Un flux soudain vide alors que des nuits étaient bloquées est refusé : il ne libère rien,
 * sauf si l'hôte confirme que le calendrier est bien vide chez la plateforme. Un événement
 * présent mais illisible garde ses nuits telles qu'elles étaient (s'il en tenait).
 *
 * Recouvrements (spec « Autres recouvrements ») : une réservation directe en attente de
 * paiement est expirée ; une réservation directe confirmée laisse le bloc en conflit et
 * l'hôte est alerté ; un blocage manuel ou un autre calendrier : conflit, sans alerte ; une
 * fermeture posée sur des nuits déjà fermées (site et plateforme) : pas un conflit.
 * Des nuits libérées lèvent les conflits qu'elles portaient. Chaque séjour ajouté, déplacé
 * ou annulé est signalé au ménage (StayScheduleChanged, après la transaction), pas une
 * simple date fermée.
 */
class IcalCalendarMerger
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BlockConflictService $conflicts,
        private readonly BookingService $bookings,
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * @param  list<ImportedEvent>  $events
     * @param  list<string>  $unreadableUids  événements présents dont les dates sont illisibles
     * @param  bool  $acceptEmpty  l'hôte confirme qu'un flux vide est normal
     *
     * @throws EmptyCalendarRefusedException flux vide suspect
     */
    public function merge(IcalFeed $feed, array $events, array $unreadableUids = [], bool $acceptEmpty = false): SyncResult
    {
        $today = CarbonImmutable::instance(Date::today());
        $upcoming = array_values(array_filter($events, fn (ImportedEvent $event) => $event->endsOn->gte($today)));

        $result = DB::transaction(function () use ($feed, $upcoming, $today, $unreadableUids, $acceptEmpty): SyncResult {
            $this->availability->lockApartment($feed->apartment_id);

            $blocks = $this->availability->feedBlocks($feed->id)->keyBy('external_uid');
            $current = $blocks->filter(fn (AvailabilityBlock $block) => $block->is_active && $block->ends_on->gt($today));
            // Seuls comptent les illisibles qui tiennent des nuits : un événement factice sans
            // date (incident de la plateforme) ne doit pas faire passer un flux vide pour lisible.
            $kept = array_values(array_filter($unreadableUids, fn (string $uid) => $current->has($uid)));
            if ($upcoming === [] && $kept === [] && $current->isNotEmpty() && ! $acceptEmpty) {
                throw new EmptyCalendarRefusedException;
            }

            // Annulations d'abord : leurs nuits peuvent accueillir un séjour déplacé.
            // Un événement illisible n'a pas disparu : son bloc reste tel quel.
            $seen = array_flip([...array_map(fn (ImportedEvent $event) => $event->uid, $upcoming), ...$kept]);
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
                } elseif ($this->apply($feed, $block, $event)) {
                    $updated++;
                }
            }

            // Relus en base : un release() a pu lever le conflit d'un bloc déjà traité.
            $conflicts = $this->availability->feedBlocks($feed->id)->filter(
                fn (AvailabilityBlock $block) => $block->is_active && $block->isRealConflict() && $block->ends_on->gt($today),
            )->count();

            return new SyncResult(IcalSyncStatus::Success, $created, $updated, $removed, $conflicts, unreadable: count($kept));
        });

        if ($result->hasChanges()) {
            $this->availabilityService->flushApartment($feed->apartment_id);
        }

        return $result;
    }

    private function create(IcalFeed $feed, ImportedEvent $event): AvailabilityBlock
    {
        $overlap = $this->overlap($feed, $event);
        $block = $this->availability->createBlock([
            'apartment_id' => $feed->apartment_id,
            'type' => BlockType::Ical,
            'ical_feed_id' => $feed->id,
            'external_uid' => $event->uid,
            ...$this->placement($feed, $event, $overlap),
            ...$this->details($event),
        ]);
        $this->alertIfDoubleBooked($block, $overlap);
        if ($block->concernsHousekeeping()) {
            StayScheduleChanged::dispatch(StayChange::added($block));
        }

        return $block;
    }

    /**
     * Met le bloc à jour ; renvoie true si ses nuits ont changé (déplacé ou réactivé).
     * Un bloc en conflit est réévalué à chaque passage : le conflit a pu disparaître.
     */
    private function apply(IcalFeed $feed, AvailabilityBlock $block, ImportedEvent $event): bool
    {
        // Relu : un bloc précédent du même flux a pu lever son conflit en base (release).
        $block->refresh();
        $moved = ! $block->is_active
            || ! $block->starts_on->equalTo($event->startsOn)
            || ! $block->ends_on->equalTo($event->endsOn);

        if (! $moved && ! $block->has_conflict) {
            $this->availability->updateBlock($block, $this->details($event));

            return false;
        }

        // Avant de relire l'état du bloc : une réservation en attente expirée ici lève ses
        // conflits (release) et a pu déjà prévenir le ménage de ce bloc.
        $overlap = $this->overlap($feed, $event, $block->id);
        $block->refresh();
        $previous = $block->is_active ? [$block->starts_on, $block->ends_on] : null;
        $housekeeping = $block->knownToHousekeeping();
        $this->availability->updateBlock($block, [...$this->placement($feed, $event, $overlap), ...$this->details($event)]);

        if (! $moved) {
            // Conflit réévalué, mêmes nuits : le bloc a pu passer de date fermée à séjour.
            $this->conflicts->notifyHousekeeping($block, $housekeeping);

            return false;
        }

        $this->alertIfDoubleBooked($block, $overlap);
        if ($previous !== null) {
            $this->conflicts->release($block->apartment_id, ...$previous);
        }
        // Ménage prévenu : séjour déplacé, de nouveau attendu (annulé puis revenu, ou date
        // fermée devenue séjour), ou retiré de son planning (séjour devenu date fermée).
        // ($housekeeping implique un bloc déjà actif, donc $previous connu.)
        $concerns = $block->concernsHousekeeping();
        $change = match (true) {
            $housekeeping && $previous !== null => $concerns ? StayChange::moved($block, ...$previous) : StayChange::cancelled($block, ...$previous),
            $concerns => StayChange::added($block),
            default => null,
        };
        if ($change !== null) {
            StayScheduleChanged::dispatch($change);
        }

        return true;
    }

    /** Séjour annulé chez la plateforme : nuits libérées, conflits qu'elles portaient levés. */
    private function deactivate(AvailabilityBlock $block): void
    {
        // Relu : un release() plus tôt dans la boucle a pu en faire un séjour connu du ménage.
        $block->refresh();
        $concernsHousekeeping = $block->concernsHousekeeping();
        $this->availability->updateBlock($block, ['is_active' => false, 'has_conflict' => false, 'is_redundant_closure' => false]);
        $this->conflicts->release($block->apartment_id, $block->starts_on, $block->ends_on);
        if ($concernsHousekeeping) {
            StayScheduleChanged::dispatch(StayChange::cancelled($block));
        }
    }

    /**
     * Blocs déjà sur ces nuits. Une réservation directe encore en attente de paiement
     * cède sa place à un séjour de la plateforme : il existe déjà chez elle, elle est expirée
     * sur-le-champ (pas de conflit). Pas devant ce qui n'est que l'écho de l'export du site
     * (Airbnb « Not available », date Booking.com posée sur des nuits toutes déjà occupées) :
     * la réservation en cours de paiement reste. Une date Booking.com qui déborde sur des
     * nuits libres est un vrai séjour : la réservation en attente expire.
     */
    private function overlap(IcalFeed $feed, ImportedEvent $event, ?int $exceptBlockId = null): BlockOverlap
    {
        $apartmentId = $feed->apartment_id;
        $overlap = $this->conflicts->check($apartmentId, $event->startsOn, $event->endsOn, $exceptBlockId);

        $pending = $overlap->pendingBookings();
        if ($pending->isEmpty() || $this->isEcho($feed, $event, $overlap)) {
            return $overlap;
        }
        $pending->each(fn (Booking $booking) => $this->bookings->expire($booking));

        return $this->conflicts->check($apartmentId, $event->startsOn, $event->endsOn, $exceptBlockId);
    }

    /** Bloc qui ne fait que reprendre des nuits déjà occupées sur le site (voir isRedundantClosure). */
    private function isEcho(IcalFeed $feed, ImportedEvent $event, BlockOverlap $overlap): bool
    {
        return $feed->provider->isClosure($event->summary)
            || ($feed->provider->mixesStaysAndClosures() && $overlap->coversAllNights($event->startsOn, $event->endsOn));
    }

    /**
     * Nuits du bloc, actif, en conflit s'il recouvre des nuits déjà occupées.
     *
     * @return array<string, mixed>
     */
    private function placement(IcalFeed $feed, ImportedEvent $event, BlockOverlap $overlap): array
    {
        return [
            'starts_on' => $event->startsOn->toDateString(),
            'ends_on' => $event->endsOn->toDateString(),
            'is_active' => true,
            'has_conflict' => $overlap->isConflict(),
            // Même date fermée ici et sur la plateforme : pas un conflit à signaler.
            'is_redundant_closure' => $this->conflicts->isRedundantClosure($this->candidate($feed, $event), $overlap),
        ];
    }

    /** Le bloc tel que l'événement le placerait, non enregistré. */
    private function candidate(IcalFeed $feed, ImportedEvent $event): AvailabilityBlock
    {
        return (new AvailabilityBlock)->forceFill([
            'apartment_id' => $feed->apartment_id,
            'type' => BlockType::Ical,
            'ical_feed_id' => $feed->id,
            'starts_on' => $event->startsOn->toDateString(),
            'ends_on' => $event->endsOn->toDateString(),
            'note' => $event->summary,
        ])->setRelation('icalFeed', $feed);
    }

    /**
     * Deux voyageurs attendus sur les mêmes nuits : l'hôte est prévenu sans attendre. Une date
     * fermée de la plateforme n'amène aucun voyageur : pas d'alerte.
     */
    private function alertIfDoubleBooked(AvailabilityBlock $block, BlockOverlap $overlap): void
    {
        // Écho de l'export du site (fermeture redondante) : pas de double réservation.
        if ($block->isClosure() || $block->is_redundant_closure) {
            return;
        }
        $bookings = $overlap->confirmedBookings();
        if ($bookings->isNotEmpty()) {
            IcalConflictDetected::dispatch($block, $bookings);
        }
    }

    /**
     * @return array{note: string|null, external_url: string|null}
     */
    private function details(ImportedEvent $event): array
    {
        return ['note' => $event->summary, 'external_url' => $event->url];
    }
}
