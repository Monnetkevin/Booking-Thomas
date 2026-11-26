<?php

namespace App\Services\Coupon;

use App\Enums\CouponType;
use App\Exceptions\Coupon\CouponInvalidException;
use App\Models\Apartment;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Booking\Stay;
use App\Services\Extra\ExtraSelector;
use App\Services\Extra\SelectedExtra;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Choix de la réduction d'un séjour (spec « Codes promo ») : un seul avantage par
 * réservation. Un code saisi remplace l'offre automatique ; sans code, la plus avantageuse
 * des offres automatiques dont les conditions sont remplies s'applique : celle qui donne le
 * total le plus bas (une option offerte que le voyageur n'a pas demandée ne baisse pas son
 * prix). Une option offerte est ajoutée au séjour si elle n'y est pas encore et si elle est
 * encore commandable. La limite par e-mail ne vaut que pour un code saisi, vérifiée dès que
 * l'e-mail est connu (création de la réservation) : une offre automatique n'en a pas, pour que
 * le prix du devis reste celui de la réservation.
 */
class CouponResolver
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly PriceCalculator $calculator,
        private readonly ExtraSelector $extras,
    ) {}

    /**
     * @param  list<SelectedExtra>  $selected
     * @param  string|null  $email  e-mail du voyageur, connu à la création de la réservation
     *
     * @throws CouponInvalidException code saisi qui ne s'applique pas
     */
    public function quote(Apartment $apartment, Stay $stay, array $selected, ?string $code, ?string $email = null): Quote
    {
        if ($code !== null && trim($code) !== '') {
            $coupon = $this->coupons->findByCode($code);
            if ($coupon === null || $coupon->automatic || ! $this->eligible($coupon, $apartment, $stay, $email)) {
                throw new CouponInvalidException;
            }
            $quote = $this->priced($coupon, $apartment, $stay, $selected);
            if ($quote === null) {
                throw new CouponInvalidException;
            }

            return $quote;
        }

        $best = null;
        foreach ($this->coupons->activeAutomatic() as $offer) {
            $quote = $this->eligible($offer, $apartment, $stay, $email) ? $this->priced($offer, $apartment, $stay, $selected) : null;
            if ($quote !== null && ($best === null || $quote->total < $best->total)) {
                $best = $quote;
            }
        }

        return $best ?? $this->calculator->calculate($apartment, $stay, $selected);
    }

    /** Conditions connues sans le prix : actif, période de validité, logement, nuits, utilisations (au total, et par e-mail s'il est connu pour un code saisi). */
    public function eligible(Coupon $coupon, Apartment $apartment, Stay $stay, ?string $email = null): bool
    {
        $today = CarbonImmutable::instance(Date::today());

        return $coupon->is_active
            && ($coupon->starts_on === null || $today->gte($coupon->starts_on))
            && ($coupon->ends_on === null || $today->lte($coupon->ends_on))
            && ($coupon->apartments->isEmpty() || $coupon->apartments->contains('id', $apartment->id))
            && ($coupon->min_nights === null || $stay->nights() >= $coupon->min_nights)
            && ($coupon->max_uses === null || $this->coupons->usesCount($coupon) < $coupon->max_uses)
            && ($email === null || $coupon->automatic || $coupon->max_uses_per_email === null || $this->coupons->usesCount($coupon, $email) < $coupon->max_uses_per_email);
    }

    /**
     * Devis avec cette réduction ; null si elle ne vaut rien pour ce séjour (montant minimum,
     * option visée absente ou plus commandable).
     *
     * @param  list<SelectedExtra>  $selected
     */
    private function priced(Coupon $coupon, Apartment $apartment, Stay $stay, array $selected): ?Quote
    {
        if ($coupon->type === CouponType::FreeExtra) {
            $selected = $this->withFreeExtra($coupon, $apartment, $stay, $selected);
        }
        $quote = $this->calculator->calculate($apartment, $stay, $selected, $coupon);

        return $quote->discount > 0 ? $quote : null;
    }

    /**
     * @param  list<SelectedExtra>  $selected
     * @return list<SelectedExtra>
     */
    private function withFreeExtra(Coupon $coupon, Apartment $apartment, Stay $stay, array $selected): array
    {
        foreach ($selected as $item) {
            if ($item->extra->id === $coupon->extra_id) {
                return $selected;
            }
        }
        $extra = $this->extras->available($apartment, $stay->arrival)->firstWhere('id', $coupon->extra_id);
        if ($extra === null) {
            return $selected;
        }
        $quantity = $extra->pricing->hasQuantity() ? min($coupon->free_quantity, $extra->max_quantity) : 1;

        return [...$selected, new SelectedExtra($extra, $quantity)];
    }
}
