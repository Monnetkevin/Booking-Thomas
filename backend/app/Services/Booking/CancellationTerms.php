<?php

namespace App\Services\Booking;

use App\Enums\CancellationPolicy;
use App\Repositories\Contracts\SettingRepositoryInterface;

/**
 * Barèmes d'annulation (réglages de l'hôte) : paliers « jours avant l'arrivée → part
 * remboursée ». Affichés sur la fiche et copiés dans chaque réservation à sa création.
 */
class CancellationTerms
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    /**
     * Paliers du barème, du plus tôt au plus tard.
     *
     * @return list<array{days_before: int, refund_percent: int}>
     */
    public function tiers(CancellationPolicy $policy): array
    {
        /** @var array<string, list<array{days_before: int, refund_percent: int}>> $policies */
        $policies = $this->settings->get('cancellation_policies', []);

        return $policies[$policy->value] ?? [];
    }

    /**
     * Copie figée dans la réservation : changer le barème ensuite ne la modifie pas.
     *
     * @return array{policy: string, tiers: list<array{days_before: int, refund_percent: int}>}
     */
    public function snapshot(CancellationPolicy $policy): array
    {
        return ['policy' => $policy->value, 'tiers' => $this->tiers($policy)];
    }
}
