<?php

namespace App\Services\PlatformRevenue;

use App\Enums\AuditAction;
use App\Enums\IcalProvider;
use App\Models\Apartment;
use App\Models\PlatformMonthlyRevenue;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\PlatformRevenueRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Revenus des plateformes saisis à la main (spec « Back-office › Statistiques ») : l'iCal ne
 * transmet aucun montant et les exports des plateformes ne détaillent pas les séjours. Chaque
 * mois, l'admin reporte le net versé par Airbnb et Booking.com pour chaque logement ; les nuits
 * viennent de la synchronisation iCal.
 */
class PlatformRevenueService
{
    /** Mois montrés dans l'historique, mois en cours compris. */
    public const HISTORY = 12;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly PlatformRevenueRepositoryInterface $revenues,
        private readonly SoldStayBlocks $soldStays,
        private readonly PlatformRevenueLedger $ledger,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Feuille d'un mois : une ligne par logement, nuits et net de chaque plateforme.
     *
     * @return array<string, mixed>
     */
    public function sheet(CarbonImmutable $month, CarbonImmutable $today): array
    {
        $month = $month->startOfMonth();
        $end = $month->addMonth();
        $apartments = $this->apartments->allOrdered();
        // Montants des seuls logements existants : ceux d'un logement supprimé ne comptent plus.
        $revenues = $this->revenues->between($month, $end)->whereIn('apartment_id', $apartments->modelKeys());
        $cells = $this->ledger->months($this->soldStays->between($month, $end), $revenues, $month, $end, $apartments->modelKeys())[$month->format('Y-m')];
        $last = $revenues->sortByDesc('updated_at')->first();

        return [
            'month' => $month->format('Y-m'),
            'is_current' => $month->isSameMonth($today),
            'apartments' => $apartments->map(fn (Apartment $apartment) => [
                'id' => $apartment->id,
                'name' => $apartment->getTranslation('name', 'fr'),
                'platforms' => $this->row($cells[$apartment->id] ?? []),
            ])->values()->all(),
            'totals' => $this->ledger->totals($cells),
            // Mois en cours : pas encore versé, rien n'est « à saisir » (comme dans les statistiques).
            'missing' => $month->isSameMonth($today) ? 0 : $this->ledger->missing($cells),
            'last_saved' => $last === null ? null : [
                'at' => $last->updated_at?->toIso8601String(),
                'by' => $last->user?->name,
            ],
        ];
    }

    /**
     * Enregistre les montants envoyés (null : efface le montant, il redevient à saisir). Seules
     * les cases dont le montant change sont écrites et journalisées (avant, après) : la saisie
     * d'un autre admin sur d'autres cases n'est ni effacée ni attribuée à celui qui enregistre.
     *
     * @param  list<array{apartment_id: int, platform: IcalProvider, net_amount: int|null}>  $entries
     * @return array<string, mixed> la feuille à jour
     */
    public function save(CarbonImmutable $month, array $entries, ?int $userId, CarbonImmutable $today): array
    {
        $month = $month->startOfMonth();
        $changes = DB::transaction(function () use ($month, $entries, $userId): array {
            $stored = [];
            foreach ($this->revenues->between($month, $month->addMonth()) as $revenue) {
                $stored[$revenue->apartment_id.'|'.$revenue->platform->value] = $revenue->net_amount;
            }

            $changes = ['before' => [], 'after' => []];
            foreach ($entries as $entry) {
                $before = $stored[$entry['apartment_id'].'|'.$entry['platform']->value] ?? null;
                if ($before === $entry['net_amount']) {
                    continue;
                }
                $entry['net_amount'] === null
                    ? $this->revenues->forget($entry['platform'], $entry['apartment_id'], $month)
                    : $this->revenues->put($entry['platform'], $entry['apartment_id'], $month, $entry['net_amount'], $userId);
                $cell = ['apartment_id' => $entry['apartment_id'], 'platform' => $entry['platform']->value];
                $changes['before'][] = [...$cell, 'net_amount' => $before];
                $changes['after'][] = [...$cell, 'net_amount' => $entry['net_amount']];
            }

            return $changes;
        });

        if ($changes['after'] !== []) {
            $this->audit->record(
                AuditAction::PlatformRevenuesSaved,
                before: ['month' => $month->format('Y-m'), 'amounts' => $changes['before']],
                after: ['month' => $month->format('Y-m'), 'amounts' => $changes['after']],
            );
        }

        return $this->sheet($month, $today);
    }

    /**
     * Les HISTORY derniers mois, le plus récent d'abord : net par plateforme et cases à saisir.
     *
     * @return list<array{month: string, is_current: bool, totals: array<string, int>, missing: int, entered: bool}>
     */
    public function history(CarbonImmutable $today): array
    {
        $to = $today->startOfMonth()->addMonth();
        $from = $to->subMonths(self::HISTORY);
        $apartments = $this->apartments->allOrdered();
        $revenues = $this->revenues->between($from, $to)->whereIn('apartment_id', $apartments->modelKeys());
        $months = $this->ledger->months($this->soldStays->between($from, $to), $revenues, $from, $to, $apartments->modelKeys());
        $entered = $revenues->mapWithKeys(fn (PlatformMonthlyRevenue $revenue) => [$revenue->month->format('Y-m') => true]);

        $history = [];
        foreach (array_reverse($months, true) as $key => $cells) {
            $history[] = [
                'month' => (string) $key,
                'is_current' => $key === $today->format('Y-m'),
                'totals' => $this->ledger->totals($cells),
                'missing' => $key === $today->format('Y-m') ? 0 : $this->ledger->missing($cells),
                'entered' => $entered->has($key),
            ];
        }

        return $history;
    }

    /**
     * @param  array<string, array{nights: int, net_amount: int|null}>  $cells
     * @return array<string, array{nights: int, net_amount: int|null}>
     */
    private function row(array $cells): array
    {
        $row = [];
        foreach (IcalProvider::cases() as $platform) {
            $row[$platform->value] = $cells[$platform->value] ?? ['nights' => 0, 'net_amount' => null];
        }

        return $row;
    }
}
