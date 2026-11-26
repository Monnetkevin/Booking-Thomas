<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

interface CouponRepositoryInterface
{
    /** Code saisi (insensible à la casse), verrouillé si demandé (enregistrement d'une utilisation). */
    public function findByCode(string $code, bool $lock = false): ?Coupon;

    /** @return Collection<int, Coupon> */
    public function activeAutomatic(): Collection;

    /** Utilisations qui comptent (pas libérées). */
    public function usesCount(Coupon $coupon, ?string $email = null): int;

    public function recordRedemption(Coupon $coupon, Booking $booking, string $email, int $discount): void;

    /** Réservation expirée avant paiement : son utilisation ne compte plus. */
    public function releaseForBooking(Booking $booking): void;
}
