<?php

namespace App\Services\Pricing;

use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\Stay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Seul endroit où un prix est calculé (devis et création de réservation). Le navigateur
 * ne calcule jamais un prix. Tous les montants sont des entiers en centimes.
 *
 * - Hébergement : nuit par nuit, chaque nuit au tarif de sa période, plus le supplément
 *   week-end de la période pour les nuits du vendredi et du samedi.
 * - Ménage : forfait du logement.
 * - Taxe de séjour : par adulte et par nuit (mineurs exonérés), forfait ou pourcentage
 *   plafonné selon le réglage « tourist_tax ».
 */
class PriceCalculator
{
    /** Nuits du vendredi et du samedi (ISO : 5 et 6). */
    private const WEEKEND_NIGHTS = [5, 6];

    public function __construct(
        private readonly RatePeriodRepositoryInterface $periods,
        private readonly SettingRepositoryInterface $settings,
    ) {}

    /**
     * @throws MissingRateException
     */
    public function calculate(Apartment $apartment, Stay $stay): Quote
    {
        $periods = $this->periods->covering($apartment->id, $stay->arrival->toDateString(), $stay->departure->subDay()->toDateString());

        $nights = array_map(fn (CarbonImmutable $date) => $this->night($periods, $date), $stay->nightDates());
        $accommodation = array_sum(array_map(fn (array $night) => $night['price'], $nights));
        $cleaning = $apartment->cleaning_fee;
        $tax = $this->touristTax($nights, $stay);

        $lines = [...$this->accommodationLines($nights)];
        if ($cleaning > 0) {
            $lines[] = ['type' => 'cleaning', 'label' => __('pricing.cleaning'), 'amount' => $cleaning];
        }
        if ($tax > 0) {
            $lines[] = [
                'type' => 'tourist_tax',
                'label' => __('pricing.tourist_tax', ['adults' => $stay->adults, 'nights' => count($nights)]),
                'amount' => $tax,
            ];
        }

        return new Quote($nights, $accommodation, $cleaning, $tax, $lines, $accommodation + $cleaning + $tax);
    }

    /**
     * @param  Collection<int, RatePeriod>  $periods
     * @return array{date: string, price: int, weekend_supplement: int}
     */
    private function night(Collection $periods, CarbonImmutable $date): array
    {
        $period = $periods->first(fn (RatePeriod $p) => $date->betweenIncluded($p->starts_on, $p->ends_on));
        if (! $period) {
            throw new MissingRateException("Aucun tarif pour la nuit du {$date->toDateString()}");
        }

        $supplement = in_array($date->dayOfWeekIso, self::WEEKEND_NIGHTS, true) ? (int) $period->weekend_supplement : 0;

        return [
            'date' => $date->toDateString(),
            'price' => $period->nightly_price + $supplement,
            'weekend_supplement' => $supplement,
        ];
    }

    /**
     * Une ligne par prix de nuit : « 2 nuits × 95 € », « 1 nuit × 110 € ».
     *
     * @param  list<array{date: string, price: int, weekend_supplement: int}>  $nights
     * @return list<array{type: string, label: string, amount: int, quantity: int, unit_amount: int}>
     */
    private function accommodationLines(array $nights): array
    {
        return collect($nights)
            ->groupBy('price')
            ->map(fn (Collection $group, int|string $price) => [
                'type' => 'accommodation',
                'label' => trans_choice('pricing.nights', $group->count(), [
                    'count' => $group->count(),
                    'price' => self::euros((int) $price),
                ]),
                'quantity' => $group->count(),
                'unit_amount' => (int) $price,
                'amount' => $group->count() * (int) $price,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{date: string, price: int, weekend_supplement: int}>  $nights
     */
    private function touristTax(array $nights, Stay $stay): int
    {
        /** @var array{mode?: string, amount?: int, percentage?: float|null, cap_per_adult_night?: int|null} $setting */
        $setting = $this->settings->get('tourist_tax', []);

        if ($stay->adults === 0) {
            return 0;
        }

        if (($setting['mode'] ?? 'per_adult_night') === 'percentage') {
            // Pourcentage du prix de la nuit par voyageur, plafonné, dû pour chaque adulte.
            $rate = (float) ($setting['percentage'] ?? 0) / 100;
            $cap = $setting['cap_per_adult_night'] ?? null;

            return array_sum(array_map(function (array $night) use ($rate, $cap, $stay) {
                $perPerson = (int) round($night['price'] / max(1, $stay->guests()) * $rate);

                return ($cap === null ? $perPerson : min($perPerson, (int) $cap)) * $stay->adults;
            }, $nights));
        }

        return (int) ($setting['amount'] ?? 0) * $stay->adults * count($nights);
    }

    /** 9500 → « 95 € », 9550 → « 95,50 € » (libellés des lignes). */
    public static function euros(int $cents): string
    {
        $formatted = number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, ',', "\u{202F}");

        return $formatted."\u{00A0}€";
    }
}
