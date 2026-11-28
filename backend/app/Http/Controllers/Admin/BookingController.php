<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bookings\CancelBookingRequest;
use App\Http\Requests\Admin\Bookings\ListBookingsRequest;
use App\Http\Requests\Admin\Bookings\StoreManualBookingRequest;
use App\Http\Requests\Admin\Bookings\UpdateBookingRequest;
use App\Http\Resources\Admin\AdminBookingListResource;
use App\Http\Resources\Admin\AdminBookingResource;
use App\Http\Responses\CsvDownload;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\Booking\AdminBookingService;
use App\Services\Booking\BookingConfirmationPdf;
use App\Services\Booking\BookingHistory;
use App\Services\Booking\BookingService;
use App\Services\Booking\ManualBookingService;
use App\Services\Privacy\GuestPrivacyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Réservations dans le back-office : liste, fiche, coordonnées, annulation, confirmation. */
class BookingController extends Controller
{
    public function __construct(
        private readonly AdminBookingService $bookings,
        private readonly BookingService $lifecycle,
        private readonly BookingConfirmationPdf $pdf,
        private readonly ManualBookingService $manual,
        private readonly BookingHistory $history,
        private readonly GuestPrivacyService $privacy,
    ) {}

    public function index(ListBookingsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        $filters = $request->filters();

        return AdminBookingListResource::collection($this->bookings->list($filters))
            ->additional(['meta' => ['counts' => $this->bookings->stageCounts($filters)]]);
    }

    /** Liste filtrée au format CSV (tableur). */
    public function csv(ListBookingsRequest $request): Response
    {
        Gate::authorize('viewAny', Booking::class);

        return CsvDownload::make($this->bookings->csv($request->filters()), 'reservations');
    }

    /** Réservation saisie par l'hôte (téléphone, connaissance), confirmée tout de suite. */
    public function store(StoreManualBookingRequest $request): JsonResponse
    {
        Gate::authorize('create', Booking::class);
        // Un total différent du prix calculé est une modification de prix : administrateur seulement.
        if ($request->total() !== null) {
            Gate::authorize('managePricing', Apartment::class);
        }

        $booking = $this->manual->create(
            $request->integer('apartment_id'),
            $request->stay(),
            $request->guest(),
            $request->total(),
            $request->boolean('paid'),
            $request->boolean('send_confirmation'),
            $request->extras(),
        );

        return $this->resource($booking)->response()->setStatusCode(201);
    }

    public function show(Booking $booking): AdminBookingResource
    {
        Gate::authorize('view', $booking);

        return $this->resource($booking);
    }

    public function update(UpdateBookingRequest $request, Booking $booking): AdminBookingResource
    {
        Gate::authorize('update', $booking);

        return $this->resource($this->bookings->update($booking, $request->bookingData()));
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): AdminBookingResource
    {
        Gate::authorize('cancel', $booking);

        return $this->resource($this->bookings->cancel($booking, $request->notifyGuest()));
    }

    public function resendConfirmation(Booking $booking): Response
    {
        Gate::authorize('update', $booking);

        $this->bookings->resendConfirmation($booking);

        return response()->noContent();
    }

    public function pdf(Booking $booking): Response
    {
        Gate::authorize('view', $booking);
        abort_unless($this->lifecycle->hasConfirmation($booking), 404);

        return response($this->pdf->render($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->pdf->filename($booking).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Données du voyageur (toutes ses réservations et ses messages), en fichier JSON. */
    public function guestData(Booking $booking): JsonResponse
    {
        Gate::authorize('managePersonalData', $booking);

        return response()->json($this->privacy->export($booking), 200, [
            'Content-Disposition' => 'attachment; filename="donnees-voyageur-'.$booking->reference.'.json"',
            'Cache-Control' => 'private, no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Anonymise le voyageur à sa demande : refusé tant qu'un de ses séjours n'est pas terminé. */
    public function anonymize(Booking $booking): AdminBookingResource
    {
        Gate::authorize('managePersonalData', $booking);

        $this->privacy->anonymize($booking);

        return $this->resource($booking->refresh());
    }

    private function resource(Booking $booking): AdminBookingResource
    {
        $booking = $this->bookings->details($booking);

        return new AdminBookingResource($booking, $this->bookings->refundProposal($booking), $this->history->of($booking, CarbonImmutable::today()));
    }
}
