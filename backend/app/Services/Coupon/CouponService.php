<?php

namespace App\Services\Coupon;

use App\Enums\AuditAction;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;

/**
 * Administration des codes promo et offres (spec « Back-office › Coupons ») : création,
 * modification, activation, nombre d'utilisations et montant remisé par code. Une
 * réservation garde sa réduction figée si le code change ensuite.
 */
class CouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, Coupon>
     */
    public function list(): Collection
    {
        return $this->coupons->allWithUsage();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function create(array $attributes, array $apartmentIds): Coupon
    {
        $coupon = $this->coupons->create($attributes, $apartmentIds);
        $this->audit->record(AuditAction::CouponCreated, $coupon, after: $this->auditValues($coupon));

        return $coupon;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function update(Coupon $coupon, array $attributes, array $apartmentIds): Coupon
    {
        $before = $this->auditValues($coupon->load('apartments'));
        $coupon = $this->coupons->update($coupon, $attributes, $apartmentIds);
        $this->audit->record(AuditAction::CouponUpdated, $coupon, $before, $this->auditValues($coupon));

        return $coupon;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Coupon $coupon): array
    {
        return [
            ...$coupon->only(['code', 'value', 'extra_id', 'free_quantity', 'max_uses', 'max_uses_per_email', 'min_nights', 'min_amount', 'automatic', 'is_active']),
            'target' => $coupon->target->value,
            'type' => $coupon->type->value,
            'starts_on' => $coupon->starts_on?->toDateString(),
            'ends_on' => $coupon->ends_on?->toDateString(),
            'apartment_ids' => $coupon->apartments->modelKeys(),
        ];
    }
}
