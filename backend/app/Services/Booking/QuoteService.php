<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\StayNotAllowedException;
use App\Exceptions\Coupon\CouponInvalidException;
use App\Exceptions\Extra\ExtraNotAvailableException;
use App\Models\Apartment;
use App\Services\Coupon\CouponResolver;
use App\Services\Extra\ExtraChoice;
use App\Services\Extra\ExtraSelector;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\Quote;

/**
 * Devis d'un séjour : règles vérifiées, options vérifiées (ExtraSelector), réduction
 * choisie (CouponResolver : code saisi ou meilleure offre automatique), puis prix calculé
 * par PriceCalculator.
 */
class QuoteService
{
    public function __construct(
        private readonly StayRules $rules,
        private readonly PriceCalculator $calculator,
        private readonly ExtraSelector $extras,
        private readonly CouponResolver $coupons,
    ) {}

    /**
     * Devis voyageur : toutes les règles doivent être respectées, et chaque option être
     * commandée avant son délai.
     *
     * @param  list<ExtraChoice>  $extras
     * @param  string|null  $coupon  code promo saisi par le voyageur
     * @param  string|null  $email  e-mail du voyageur, s'il est connu (limite d'utilisation par voyageur)
     *
     * @throws StayNotAllowedException
     * @throws ExtraNotAvailableException
     * @throws CouponInvalidException
     */
    public function quote(Apartment $apartment, Stay $stay, array $extras = [], ?string $coupon = null, ?string $email = null): Quote
    {
        $violations = $this->rules->violations($apartment, $stay);
        if ($violations !== []) {
            throw new StayNotAllowedException($violations);
        }

        return $this->coupons->quote($apartment, $stay, $this->extras->select($apartment, $stay, $extras), $coupon, $email);
    }

    /**
     * Aperçu de l'hôte (écran Tarifs) : prix affiché dès que chaque nuit a un tarif, les
     * règles non respectées (séjour minimum…) étant signalées à côté. Un séjour plus long
     * que le maximum n'est pas chiffré : les tarifs n'y sont pas vérifiés nuit par nuit.
     *
     * Les options choisies par l'hôte (réservation à la main) ne sont pas tenues au délai.
     *
     * @param  list<ExtraChoice>  $extras
     * @return array{quote: Quote|null, warnings: list<array{code: string, message: string}>}
     *
     * @throws ExtraNotAvailableException
     */
    public function preview(Apartment $apartment, Stay $stay, array $extras = []): array
    {
        $warnings = $this->rules->violations($apartment, $stay, forHost: true);
        $priceable = array_intersect(['no_rate', 'max_stay'], array_column($warnings, 'code')) === [];

        return [
            'quote' => $priceable ? $this->calculator->calculate($apartment, $stay, $this->extras->select($apartment, $stay, $extras, enforceLeadTime: false)) : null,
            'warnings' => $warnings,
        ];
    }
}
