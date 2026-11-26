<?php

namespace App\Services\Booking;

use App\Enums\BookingSource;
use App\Enums\BookingStage;
use App\Enums\BookingStatus;

/**
 * Filtres de la liste des réservations du back-office : statut, logement, source,
 * séjours qui touchent la période [from, to[, recherche (référence, nom ou e-mail),
 * onglet (à venir, en cours, terminées, annulées) apprécié au jour `today`.
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
        public ?BookingStage $stage = null,
        public ?string $today = null,
    ) {}

    /** Mêmes filtres, sans l'onglet : base des compteurs d'onglets. */
    public function withoutStage(): self
    {
        return new self($this->status, $this->apartmentId, $this->source, $this->from, $this->to, $this->search, null, $this->today);
    }

    public function withStage(BookingStage $stage): self
    {
        return new self($this->status, $this->apartmentId, $this->source, $this->from, $this->to, $this->search, $stage, $this->today);
    }
}
