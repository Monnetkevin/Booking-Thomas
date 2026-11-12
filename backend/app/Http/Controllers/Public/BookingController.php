<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreBookingRequest;
use App\Http\Resources\Public\BookingTrackingResource;
use App\Http\Resources\Public\PendingBookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingConfirmationPdf;
use App\Services\Booking\BookingService;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * POST /api/bookings : réservation en attente de paiement, nuits bloquées 30 minutes.
 * GET /api/bookings/{référence} : suivi par le voyageur, depuis son lien signé.
 * GET /api/bookings/{référence}/pdf : confirmation PDF (lien signé).
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly BookingService $bookings,
        private readonly BookingConfirmationPdf $pdf,
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

    /** Confirmation PDF, seulement pour une réservation confirmée (lien signé). */
    public function pdf(Booking $booking): Response
    {
        abort_unless($this->bookings->hasConfirmation($booking), 404);

        return response($this->pdf->render($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->pdf->filename($booking).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Signature vérifiée par le middleware « signed:relative » de la route. */
    public function show(Booking $booking): BookingTrackingResource
    {
        return new BookingTrackingResource($this->bookings->forTracking($booking));
    }
}
