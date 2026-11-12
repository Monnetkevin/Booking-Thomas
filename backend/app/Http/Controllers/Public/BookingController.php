<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreBookingRequest;
use App\Http\Resources\Public\PendingBookingResource;
use App\Services\Booking\BookingService;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;

/** POST /api/bookings : réservation en attente de paiement, nuits bloquées 30 minutes. */
class BookingController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly BookingService $bookings,
    ) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $apartment = $this->catalog->findPublished($request->string('apartment')->toString());

        $booking = $this->bookings->createPending(
            $apartment,
            $request->stay(),
            $request->guest(),
            app()->getLocale(),
            $request->input('captcha_token'),
            $request->ip(),
        );

        return (new PendingBookingResource($booking))->response()->setStatusCode(201);
    }
}
