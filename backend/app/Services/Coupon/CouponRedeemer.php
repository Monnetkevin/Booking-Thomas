<?php

namespace App\Services\Coupon;

use App\Exceptions\Coupon\CouponInvalidException;
use App\Models\Booking;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Pricing\Quote;

/**
 * Utilisation d'un code ou d'une offre par une réservation, dans sa transaction : le code
 * est verrouillé, puis les limites (au total, par e-mail) revérifiées avant de compter.
 * Une offre automatique n'est pas revérifiée : le voyageur n'a rien saisi, on ne refuse pas
 * sa réservation. Deux réservations simultanées peuvent donc dépasser sa limite d'une ou
 * deux utilisations.
 */
class CouponRedeemer
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    /**
     * @throws CouponInvalidException limite atteinte entre le devis et la réservation
     */
    public function redeem(Quote $quote, Booking $booking, string $email): void
    {
        if ($quote->couponCode === null) {
            return;
        }
        $coupon = $this->coupons->findByCode($quote->couponCode, lock: true);
        if ($coupon === null) {
            throw new CouponInvalidException;
        }
        if (! $coupon->automatic && (
            ($coupon->max_uses !== null && $this->coupons->usesCount($coupon) >= $coupon->max_uses)
            || ($coupon->max_uses_per_email !== null && $this->coupons->usesCount($coupon, $email) >= $coupon->max_uses_per_email))) {
            throw new CouponInvalidException;
        }

        $this->coupons->recordRedemption($coupon, $booking, $email, $quote->discount);
    }

    public function release(Booking $booking): void
    {
        $this->coupons->releaseForBooking($booking);
    }
}
