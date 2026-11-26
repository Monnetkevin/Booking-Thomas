<?php

namespace App\Services\Coupon;

use App\Enums\AuditAction;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Administration des codes promo et offres (spec « Back-office › Coupons ») : création,
 * modification, activation, nombre d'utilisations et montant remisé par code. Une
 * réservation garde sa réduction figée si le code change ensuite.
 */
class CouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly ExtraRepositoryInterface $extras,
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
     *
     * @throws ValidationException option visée ou offerte jamais commandable avec ce code
     */
    public function create(array $attributes, array $apartmentIds): Coupon
    {
        $this->ensureExtraOffered($attributes, $apartmentIds);
        $coupon = $this->coupons->create($attributes, $apartmentIds);
        $this->audit->record(AuditAction::CouponCreated, $coupon, after: $this->auditValues($coupon));

        return $coupon;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     *
     * @throws ValidationException option visée ou offerte jamais commandable avec ce code
     */
    public function update(Coupon $coupon, array $attributes, array $apartmentIds): Coupon
    {
        $this->ensureExtraOffered($attributes, $apartmentIds);
        $before = $this->auditValues($coupon->load('apartments'));
        $coupon = $this->coupons->update($coupon, $attributes, $apartmentIds);
        $this->audit->record(AuditAction::CouponUpdated, $coupon, $before, $this->auditValues($coupon));

        return $coupon;
    }

    /**
     * L'option visée ou offerte doit être active et proposée dans un des logements du code :
     * sinon le code ne servirait jamais.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    private function ensureExtraOffered(array $attributes, array $apartmentIds): void
    {
        $extraId = $attributes['extra_id'] ?? null;
        if (is_int($extraId) && ! $this->extras->isOfferedIn($extraId, $apartmentIds)) {
            throw ValidationException::withMessages(['extra_id' => __('coupons.extra_not_offered', [], 'fr')]);
        }
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
