<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreBookingRequest;
use App\Http\Resources\Public\BookingTrackingResource;
use App\Http\Resources\Public\PendingBookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/bookings : réservation en attente de paiement, nuits bloquées 30 minutes.
 * GET /api/bookings/{référence} : suivi par le voyageur, depuis son lien signé.
 */
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

    /** Signature vérifiée par le middleware « signed:relative » de la route. */
    public function show(Booking $booking): BookingTrackingResource
    {
        return new BookingTrackingResource($this->bookings->forTracking($booking));
    }
}
