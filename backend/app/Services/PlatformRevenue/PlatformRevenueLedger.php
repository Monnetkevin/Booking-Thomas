<?php

namespace App\Services\PlatformRevenue;

use App\Enums\IcalProvider;
use App\Models\AvailabilityBlock;
use App\Models\PlatformMonthlyRevenue;
use App\Services\Statistics\NightCounter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Rapproche, mois par mois, les nuits vendues sur chaque plateforme (iCal) et le net saisi
 * par l'admin : une case par logement et plateforme qui a des nuits ou un montant. Une case
 * avec des nuits sans montant est « à saisir ». Écran de saisie et statistiques partagent ce calcul.
 */
class PlatformRevenueLedger
{
    public function __construct(private readonly NightCounter $nights) {}

    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs prêts à compter (SoldStayBlocks)
     * @param  Collection<int, PlatformMonthlyRevenue>  $revenues  montants saisis sur la période
     * @param  list<int>  $apartmentIds  logements à garder
     * @return array<string, array<int, array<string, array{nights: int, net_amount: int|null}>>> mois (« 2026-10 ») → logement → plateforme → case
     */
    public function months(Collection $blocks, Collection $revenues, CarbonImmutable $from, CarbonImmutable $to, array $apartmentIds): array
    {
        $keep = array_flip($apartmentIds);
        $amounts = [];
        foreach ($revenues as $revenue) {
            $amounts[$revenue->month->format('Y-m')][$revenue->apartment_id][$revenue->platform->value] = $revenue->net_amount;
        }

        $months = [];
        for ($month = $from; $month->lt($to); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $nights = $this->nights->platformNightsByApartment($blocks, $month, $month->addMonth());
            $cells = [];
            foreach (array_keys($nights + ($amounts[$key] ?? [])) as $apartmentId) {
                if (! isset($keep[$apartmentId])) {
                    continue;
                }
                foreach (IcalProvider::cases() as $platform) {
                    $count = $nights[$apartmentId][$platform->value] ?? 0;
                    $amount = $amounts[$key][$apartmentId][$platform->value] ?? null;
                    if ($count > 0 || $amount !== null) {
                        $cells[$apartmentId][$platform->value] = ['nights' => $count, 'net_amount' => $amount];
                    }
                }
            }
            $months[$key] = $cells;
        }

        return $months;
    }

    /**
     * Net saisi par plateforme, en centimes (0 pour une plateforme sans montant).
     *
     * @param  array<int, array<string, array{nights: int, net_amount: int|null}>>  $cells  cases d'un mois, ou de plusieurs fusionnées
     * @return array<string, int>
     */
    public function totals(array $cells): array
    {
        $totals = array_fill_keys(array_map(fn (IcalProvider $platform) => $platform->value, IcalProvider::cases()), 0);
        foreach ($cells as $platforms) {
            foreach ($platforms as $platform => $cell) {
                $totals[$platform] += $cell['net_amount'] ?? 0;
            }
        }

        return $totals;
    }

    /**
     * Cases « à saisir » : des nuits vendues, aucun montant.
     *
     * @param  array<int, array<string, array{nights: int, net_amount: int|null}>>  $cells
     */
    public function missing(array $cells): int
    {
        $missing = 0;
        foreach ($cells as $platforms) {
            foreach ($platforms as $cell) {
                if ($cell['nights'] > 0 && $cell['net_amount'] === null) {
                    $missing++;
                }
            }
        }

        return $missing;
    }
}
