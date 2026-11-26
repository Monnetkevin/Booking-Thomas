<?php

namespace App\Services\Pricing;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Enums\ExtraPricing;
use App\Models\Coupon;
use App\Services\Booking\Stay;

/**
 * Montant d'une réduction (spec « Tarification › Réduction ») : pourcentage ou montant fixe
 * sur l'hébergement et le ménage, sur une option précise, ou sur les deux ; ou option
 * offerte pour la quantité indiquée. Arrondi au centime, plafonné à sa base, jamais sur la
 * taxe de séjour. 0 si le montant minimum n'est pas atteint ou si rien n'est visé.
 */
class DiscountCalculator
{
    /**
     * @param  int  $stayAmount  hébergement + ménage, en centimes
     * @param  list<array{extra_id: int, pricing: string, quantity: int, unit_amount: int, amount: int}>  $extraItems
     */
    public function amount(Coupon $coupon, Stay $stay, int $stayAmount, array $extraItems): int
    {
        $item = collect($extraItems)->firstWhere('extra_id', $coupon->extra_id);
        $extraAmount = $item['amount'] ?? 0;
        // L'option offerte ne compte pas pour atteindre le montant minimum de sa propre offre.
        $counted = array_sum(array_column($extraItems, 'amount')) - ($coupon->type === CouponType::FreeExtra ? $extraAmount : 0);
        if ($coupon->min_amount !== null && $stayAmount + $counted < $coupon->min_amount) {
            return 0;
        }

        if ($coupon->type === CouponType::FreeExtra) {
            if ($item === null) {
                return 0;
            }
            $freeQuantity = min($item['quantity'], $coupon->free_quantity);

            return min($extraAmount, $item['unit_amount'] * ExtraPricing::from($item['pricing'])->units($stay, $freeQuantity));
        }

        $base = match ($coupon->target) {
            CouponTarget::Stay => $stayAmount,
            CouponTarget::Extra => $extraAmount,
            CouponTarget::Both => $stayAmount + $extraAmount,
        };

        return $coupon->type === CouponType::Percent
            ? (int) round($base * min(100, $coupon->value) / 100)
            : min($coupon->value, $base);
    }

    /**
     * Part d'une réduction qui porte sur les options : toute la réduction pour une option
     * visée ou offerte, rien pour le séjour, au prorata de la base pour les deux.
     *
     * @param  int  $stayAmount  hébergement + ménage, en centimes
     * @param  list<array{extra_id: int, pricing: string, quantity: int, unit_amount: int, amount: int}>  $extraItems
     */
    public function extrasShare(Coupon $coupon, int $discount, int $stayAmount, array $extraItems): int
    {
        if ($coupon->type === CouponType::FreeExtra) {
            return $discount;
        }
        $extraAmount = collect($extraItems)->firstWhere('extra_id', $coupon->extra_id)['amount'] ?? 0;

        return match ($coupon->target) {
            CouponTarget::Stay => 0,
            CouponTarget::Extra => $discount,
            CouponTarget::Both => $stayAmount + $extraAmount > 0 ? intdiv($discount * $extraAmount, $stayAmount + $extraAmount) : 0,
        };
    }
}
