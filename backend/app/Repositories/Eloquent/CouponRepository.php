<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CouponRepository implements CouponRepositoryInterface
{
    public function findByCode(string $code, bool $lock = false): ?Coupon
    {
        return Coupon::query()
            ->where('code', mb_strtoupper(trim($code)))
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->with(['apartments', 'extra'])
            ->first();
    }

    public function activeAutomatic(): Collection
    {
        return Coupon::query()->where('automatic', true)->where('is_active', true)->with(['apartments', 'extra'])->orderBy('id')->get();
    }

    public function usesCount(Coupon $coupon, ?string $email = null): int
    {
        return CouponRedemption::query()
            ->where('coupon_id', $coupon->id)
            ->whereNull('released_at')
            ->when($email !== null, fn (Builder $query) => $query->where('email', mb_strtolower((string) $email)))
            ->count();
    }

    public function recordRedemption(Coupon $coupon, Booking $booking, string $email, int $discount): void
    {
        CouponRedemption::query()->create([
            'coupon_id' => $coupon->id,
            'booking_id' => $booking->id,
            'email' => mb_strtolower($email),
            'code' => $coupon->code,
            'discount_amount' => $discount,
        ]);
    }

    public function releaseForBooking(Booking $booking): void
    {
        CouponRedemption::query()->where('booking_id', $booking->id)->whereNull('released_at')->update(['released_at' => now()]);
    }

    public function allWithUsage(): Collection
    {
        return Coupon::query()
            ->with(['apartments' => fn ($query) => $query->orderBy('position'), 'extra'])
            ->withCount(['redemptions as uses_count' => fn (Builder $query) => $query->whereNull('released_at')])
            ->withSum(['redemptions as discount_total' => fn (Builder $query) => $query->whereNull('released_at')], 'discount_amount')
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get();
    }

    public function create(array $attributes, array $apartmentIds): Coupon
    {
        $coupon = Coupon::query()->create($attributes);
        $coupon->apartments()->sync($apartmentIds);

        return $coupon->load(['apartments', 'extra']);
    }

    public function update(Coupon $coupon, array $attributes, array $apartmentIds): Coupon
    {
        $coupon->update($attributes);
        $coupon->apartments()->sync($apartmentIds);

        return $coupon->load(['apartments', 'extra']);
    }
}
