<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ValidateCouponRequest;
use App\Http\Resources\Public\QuoteResource;
use App\Services\Booking\QuoteService;
use App\Services\Catalog\PublicCatalogService;

/** POST /api/coupons/validate : détail du prix avec le code, ou une erreur unique et générique. */
class CouponController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly QuoteService $quotes,
    ) {}

    public function validate(ValidateCouponRequest $request): QuoteResource
    {
        $apartment = $this->catalog->findPublished($request->string('apartment')->toString());

        return new QuoteResource($this->quotes->quote($apartment, $request->stay(), $request->extras(), $request->code()));
    }
}
