<?php

namespace App\Services\Coupon;

use App\Exceptions\Coupon\CouponInvalidException;
use App\Models\Booking;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Pricing\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Utilisation d'un code ou d'une offre par une réservation, dans sa transaction. Un code
 * saisi est verrouillé puis revérifié (actif, période de validité, limites au total et par
 * e-mail) avant de compter. Une offre automatique n'est pas revérifiée : le voyageur n'a
 * rien saisi, on ne refuse pas sa réservation. Deux réservations simultanées peuvent donc
 * dépasser sa limite d'une ou deux utilisations.
 */
class CouponRedeemer
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    /**
     * @throws CouponInvalidException code désactivé, expiré ou épuisé entre le devis et la réservation
     */
    public function redeem(Quote $quote, Booking $booking, string $email): void
    {
        if ($quote->couponId === null) {
            return;
        }
        $coupon = $this->coupons->lockForRedemption($quote->couponId);
        if ($coupon === null || (! $coupon->automatic && ! $this->stillValid($coupon, $email))) {
            throw new CouponInvalidException;
        }

        $this->coupons->recordRedemption($coupon, $booking, $email, $quote->discount);
    }

    /** Réservation expirée ou annulée : le code peut resservir. */
    public function release(Booking $booking): void
    {
        $this->coupons->releaseForBooking($booking);
    }

    private function stillValid(Coupon $coupon, string $email): bool
    {
        $today = CarbonImmutable::instance(Date::today());

        return $coupon->is_active
            && ($coupon->starts_on === null || $today->gte($coupon->starts_on))
            && ($coupon->ends_on === null || $today->lte($coupon->ends_on))
            && ($coupon->max_uses === null || $this->coupons->usesCount($coupon) < $coupon->max_uses)
            && ($coupon->max_uses_per_email === null || $this->coupons->usesCount($coupon, $email) < $coupon->max_uses_per_email);
    }
}
