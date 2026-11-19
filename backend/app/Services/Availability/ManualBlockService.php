<?php

namespace App\Services\Availability;

use App\Enums\AuditAction;
use App\Enums\BlockType;
use App\Exceptions\Availability\BlockNotRemovableException;
use App\Exceptions\DatesUnavailableException;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Fermetures manuelles d'un logement (travaux, usage personnel) sur des nuits
 * [début, fin[. Une fermeture ne recouvre jamais un séjour déjà prévu : elle est refusée
 * plutôt que de créer un conflit. La retirer libère ses nuits et lève les conflits
 * qu'elle portait. Création et retrait sont journalisés.
 */
class ManualBlockService
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly BlockConflictService $conflicts,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @throws DatesUnavailableException nuits déjà occupées
     */
    public function create(Apartment $apartment, CarbonImmutable $startsOn, CarbonImmutable $endsOn, ?string $note): AvailabilityBlock
    {
        try {
            $block = DB::transaction(function () use ($apartment, $startsOn, $endsOn, $note): AvailabilityBlock {
                $this->availability->lockApartment($apartment->id);
                if ($this->availability->hasActiveBlock($apartment->id, $startsOn->toDateString(), $endsOn->toDateString())) {
                    throw DatesUnavailableException::taken();
                }

                return $this->availability->createBlock([
                    'apartment_id' => $apartment->id,
                    'type' => BlockType::Manual,
                    'starts_on' => $startsOn->toDateString(),
                    'ends_on' => $endsOn->toDateString(),
                    'note' => $note,
                    'is_active' => true,
                    'has_conflict' => false,
                ]);
            });
        } catch (QueryException $e) {
            throw DatesUnavailableException::isExclusionViolation($e) ? DatesUnavailableException::from($e) : $e;
        }

        $this->availabilityService->flushApartment($apartment->id);
        $this->audit->record(AuditAction::BlockCreated, $block, after: $this->auditValues($block));

        return $block;
    }

    /**
     * @throws BlockNotRemovableException réservation ou séjour importé
     */
    public function delete(AvailabilityBlock $block): void
    {
        if ($block->type !== BlockType::Manual) {
            throw new BlockNotRemovableException;
        }

        $before = $this->auditValues($block);
        DB::transaction(function () use ($block): void {
            $this->availability->lockApartment($block->apartment_id);
            $this->availability->deleteBlock($block);
            $this->conflicts->release($block->apartment_id, $block->starts_on, $block->ends_on);
        });

        $this->availabilityService->flushApartment($block->apartment_id);
        $this->audit->record(AuditAction::BlockDeleted, $block, before: $before);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(AvailabilityBlock $block): array
    {
        return [
            'apartment_id' => $block->apartment_id,
            'starts_on' => $block->starts_on->toDateString(),
            'ends_on' => $block->ends_on->toDateString(),
            'note' => $block->note,
        ];
    }
}
