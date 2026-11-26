<?php

namespace App\Services\Pricing;

use App\Models\Apartment;
use App\Models\Coupon;
use App\Models\RatePeriod;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\Stay;
use App\Services\Extra\SelectedExtra;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Seul endroit où un prix est calculé (devis et création de réservation). Le navigateur
 * ne calcule jamais un prix. Tous les montants sont des entiers en centimes.
 *
 * - Hébergement : nuit par nuit, chaque nuit au tarif de sa période, plus le supplément
 *   week-end de la période pour les nuits du vendredi et du samedi.
 * - Ménage : forfait du logement.
 * - Options : prix unitaire × quantité, ou × nuits et/ou voyageurs selon leur mode ;
 *   une option gratuite apparaît à 0 €.
 * - Réduction : un seul code ou offre (DiscountCalculator), sur l'hébergement et le ménage,
 *   une option, ou les deux ; jamais sur la taxe de séjour.
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
        private readonly DiscountCalculator $discounts,
    ) {}

    /**
     * @param  list<SelectedExtra>  $extras  options vérifiées (ExtraSelector)
     * @param  Coupon|null  $coupon  code ou offre déjà jugé recevable (CouponResolver) ; sans effet
     *                               si ses conditions de montant ne sont pas remplies
     *
     * @throws MissingRateException
     */
    public function calculate(Apartment $apartment, Stay $stay, array $extras = [], ?Coupon $coupon = null): Quote
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
        $items = array_map(fn (SelectedExtra $selected) => $this->extraItem($selected, $stay), $extras);
        foreach ($items as $item) {
            $lines[] = ['type' => 'extra', 'label' => $item['label'], 'quantity' => $item['quantity'], 'unit_amount' => $item['unit_amount'], 'amount' => $item['amount']];
        }
        $extrasAmount = array_sum(array_column($items, 'amount'));
        $discount = $coupon === null ? 0 : $this->discounts->amount($coupon, $stay, $accommodation + $cleaning, $items);
        // Réduction appliquée : seulement si elle vaut quelque chose pour ce séjour.
        $applied = $discount > 0 ? $coupon : null;
        if ($applied !== null) {
            $label = __($applied->automatic ? 'coupons.line_offer' : 'coupons.line_code', ['code' => $applied->code]);
            $lines[] = ['type' => 'discount', 'label' => $label, 'amount' => -$discount];
        }
        if ($tax > 0) {
            $lines[] = [
                'type' => 'tourist_tax',
                'label' => __('pricing.tourist_tax', ['adults' => $stay->adults, 'nights' => count($nights)]),
                'amount' => $tax,
            ];
        }

        return new Quote(
            $nights, $accommodation, $cleaning, $tax, $lines, $accommodation + $cleaning + $extrasAmount - $discount + $tax,
            $extrasAmount,
            $this->extraItems($stay, $extras),
            $discount,
            $applied?->id,
            $applied?->code,
            $applied === null ? 0 : $this->discounts->extrasShare($applied, $discount, $accommodation + $cleaning, $items),
        );
    }

    /**
     * Options chiffrées sans le reste du séjour (prix convenu sans tarif : options notées
     * pour être préparées).
     *
     * @param  list<SelectedExtra>  $extras
     * @return list<array{extra_id: int, name: string, pricing: string, quantity: int, unit_amount: int, amount: int}>
     */
    public function extraItems(Stay $stay, array $extras): array
    {
        return array_map(fn (SelectedExtra $selected) => array_diff_key($this->extraItem($selected, $stay), ['label' => true]), $extras);
    }

    /**
     * Option chiffrée pour ce séjour, telle qu'elle sera figée dans la réservation.
     *
     * @return array{extra_id: int, name: string, label: string, pricing: string, quantity: int, unit_amount: int, amount: int}
     */
    private function extraItem(SelectedExtra $selected, Stay $stay): array
    {
        $extra = $selected->extra;
        // Ligne du devis dans la langue du voyageur ; nom figé en français pour l'hôte et le ménage.
        $name = $extra->getTranslation('name', app()->getLocale());

        return [
            'extra_id' => $extra->id,
            'name' => $extra->getTranslation('name', 'fr'),
            'label' => $extra->pricing->hasQuantity() ? "{$selected->quantity} × $name" : $name,
            'pricing' => $extra->pricing->value,
            'quantity' => $selected->quantity,
            'unit_amount' => $extra->price,
            'amount' => $extra->price * $extra->pricing->units($stay, $selected->quantity),
        ];
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
