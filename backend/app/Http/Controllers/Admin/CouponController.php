<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupons\SaveCouponRequest;
use App\Http\Resources\Admin\CouponResource;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Codes promo et offres automatiques : liste, création, modification (dont activation). */
class CouponController extends Controller
{
    public function __construct(private readonly CouponService $coupons) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Coupon::class);

        return CouponResource::collection($this->coupons->list());
    }

    public function store(SaveCouponRequest $request): JsonResponse
    {
        Gate::authorize('create', Coupon::class);

        return (new CouponResource($this->coupons->create($request->couponAttributes(), $request->apartmentIds())))->response()->setStatusCode(201);
    }

    public function update(SaveCouponRequest $request, Coupon $coupon): CouponResource
    {
        Gate::authorize('update', $coupon);

        return new CouponResource($this->coupons->update($coupon, $request->couponAttributes(), $request->apartmentIds()));
    }
}
