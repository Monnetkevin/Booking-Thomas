<?php

namespace App\Services\Housekeeping;

use App\Enums\BlockType;
use App\Enums\CleaningMovementKind;
use App\Models\AvailabilityBlock;
use Carbon\CarbonImmutable;

/**
 * Un mouvement du planning du ménage (spec « Second rôle : ménage ») : logement, jour,
 * nature, heure, nombre de voyageurs et notes de ménage s'ils sont connus. Jamais de nom,
 * de coordonnées, de montant ni de source de réservation.
 */
final readonly class CleaningMovement
{
    public function __construct(
        public CarbonImmutable $date,
        public int $apartmentId,
        public string $apartmentName,
        public CleaningMovementKind $kind,
        public ?string $time = null,
        public ?int $guests = null,
        public ?string $notes = null,
    ) {}

    /**
     * Ce que ce bloc, qui commence ou finit ce jour-là, demande au ménage ; null pour la
     * fin d'une simple fermeture (rien à faire).
     */
    public static function fromBlock(AvailabilityBlock $block, CarbonImmutable $date): ?self
    {
        $departing = $block->ends_on->isSameDay($date);
        $make = fn (CleaningMovementKind $kind, ?string $time = null, ?int $guests = null, ?string $notes = null) => new self(
            $date, $block->apartment_id, $block->apartment->getTranslation('name', 'fr'), $kind, $time, $guests, $notes,
        );

        if ($block->type === BlockType::Manual && $block->needs_cleaning) {
            // Logement occupé pendant la fermeture (famille, amis) : ménage à la fin.
            return $make($departing ? CleaningMovementKind::OccupiedEnd : CleaningMovementKind::Occupied);
        }
        if ($block->isClosure()) {
            // Une fermeture (ici ou sur une plateforme) qui se termine ne demande rien au ménage.
            return $departing ? null : $make(CleaningMovementKind::Blocked);
        }
        if ($departing) {
            return $make(CleaningMovementKind::Departure, self::hour($block->apartment->check_out_time));
        }

        $booking = $block->booking;

        return $make(
            CleaningMovementKind::Arrival,
            self::hour($block->apartment->check_in_time),
            $booking !== null ? $booking->adults + $booking->children : null,
            $booking?->housekeeping_notes ?: null,
        );
    }

    /** « 16:00:00 » → « 16:00 » ; null si le logement n'a pas d'heure. */
    private static function hour(?string $time): ?string
    {
        return $time === null || $time === '' ? null : substr($time, 0, 5);
    }
}
