<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

interface CouponRepositoryInterface
{
    /** Code saisi (insensible à la casse), verrouillé si demandé (enregistrement d'une utilisation). */
    public function findByCode(string $code, bool $lock = false): ?Coupon;

    /** Code ou offre verrouillé pour enregistrer une utilisation. */
    public function lockForRedemption(int $id): ?Coupon;

    /** @return Collection<int, Coupon> */
    public function activeAutomatic(): Collection;

    /** Utilisations qui comptent (pas libérées). */
    public function usesCount(Coupon $coupon, ?string $email = null): int;

    public function recordRedemption(Coupon $coupon, Booking $booking, string $email, int $discount): void;

    /** Réservation expirée avant paiement ou annulée : son utilisation ne compte plus. */
    public function releaseForBooking(Booking $booking): void;

    /**
     * Tous les codes et offres, avec logements, option visée, utilisations qui comptent
     * (uses_count) et montant remisé (discount_total).
     *
     * @return Collection<int, Coupon>
     */
    public function allWithUsage(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function create(array $attributes, array $apartmentIds): Coupon;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function update(Coupon $coupon, array $attributes, array $apartmentIds): Coupon;

    /**
     * Efface l'e-mail des utilisations de code de ces réservations (elles restent comptées).
     *
     * @param  list<int>  $bookingIds
     */
    public function anonymizeRedemptions(array $bookingIds): void;
}
