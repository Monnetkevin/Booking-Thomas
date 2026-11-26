<?php

namespace App\Repositories\Contracts;

use App\Enums\IcalProvider;
use App\Models\PlatformMonthlyRevenue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

interface PlatformRevenueRepositoryInterface
{
    /**
     * Montants saisis pour les mois de [from, to[ (premiers jours de mois), auteur chargé.
     *
     * @return Collection<int, PlatformMonthlyRevenue>
     */
    public function between(CarbonImmutable $from, CarbonImmutable $to): Collection;

    /** Premier mois saisi (« 2025-01-01 »), null s'il n'y en a aucun. */
    public function earliestMonth(): ?string;

    /** Enregistre le montant (centimes) d'une plateforme, d'un logement et d'un mois. */
    public function put(IcalProvider $platform, int $apartmentId, CarbonImmutable $month, int $netAmount, ?int $userId): void;

    /** Efface le montant : il redevient « à saisir ». */
    public function forget(IcalProvider $platform, int $apartmentId, CarbonImmutable $month): void;
}
