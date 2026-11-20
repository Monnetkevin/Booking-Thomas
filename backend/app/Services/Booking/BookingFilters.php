<?php

namespace App\Services\Booking;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;

/**
 * Filtres de la liste des réservations du back-office : statut, logement, source,
 * séjours qui touchent la période [from, to[, recherche (référence, nom ou e-mail).
 */
final readonly class BookingFilters
{
    public function __construct(
        public ?BookingStatus $status = null,
        public ?int $apartmentId = null,
        public ?BookingSource $source = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $search = null,
    ) {}
}
