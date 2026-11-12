<?php

namespace App\Services\Booking;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\Booking\StayNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Availability\AvailabilityService;
use App\Services\Security\HumanVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle de vie d'une réservation directe.
 *
 * Création : défi anti-robot, devis recalculé par le serveur (jamais un montant venu du
 * navigateur), puis dans une transaction la réservation « pending » et le bloc de ses
 * nuits. La contrainte d'exclusion PostgreSQL garantit qu'aucune autre réservation ne
 * prend les mêmes nuits. Sans paiement dans les 30 minutes, la réservation expire et
 * libère les nuits (tâche planifiée chaque minute).
 */
class BookingService
{
    /** Délai pour payer avant que les nuits soient libérées. */
    public const HOLD_MINUTES = 30;

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly QuoteService $quotes,
        private readonly CancellationTerms $cancellation,
        private readonly BookingReferenceGenerator $references,
        private readonly HumanVerifier $humans,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, country: string, message?: string|null}  $guest
     *
     * @throws ValidationException défi anti-robot échoué
     * @throws StayNotAllowedException séjour hors règles (capacité, séjour minimum, dates…)
     * @throws DatesUnavailableException nuits prises entre-temps
     */
    public function createPending(Apartment $apartment, Stay $stay, array $guest, string $locale, ?string $captchaToken, ?string $ip): Booking
    {
        if (! $this->humans->verify($captchaToken, $ip)) {
            throw ValidationException::withMessages(['captcha' => __('booking.captcha_failed')]);
        }

        $quote = $this->quotes->quote($apartment, $stay);

        try {
            $booking = DB::transaction(function () use ($apartment, $stay, $guest, $locale, $quote): Booking {
                $booking = $this->bookings->create([
                    'reference' => $this->references->generate(),
                    'apartment_id' => $apartment->id,
                    'source' => BookingSource::Direct,
                    'status' => BookingStatus::Pending,
                    'guest_name' => $guest['name'],
                    'guest_email' => $guest['email'],
                    'guest_phone' => $guest['phone'] ?? null,
                    'guest_country' => $guest['country'],
                    'guest_message' => $guest['message'] ?? null,
                    'locale' => $locale,
                    'adults' => $stay->adults,
                    'children' => $stay->children,
                    'arrival' => $stay->arrival->toDateString(),
                    'departure' => $stay->departure->toDateString(),
                    'accommodation_amount' => $quote->accommodation,
                    'cleaning_fee' => $quote->cleaningFee,
                    'tourist_tax' => $quote->touristTax,
                    'total_amount' => $quote->total,
                    'price_breakdown' => $quote->toArray(),
                    'cancellation_policy' => $this->cancellation->snapshot($apartment->cancellation_policy),
                    'expires_at' => now()->addMinutes(self::HOLD_MINUTES),
                ]);
                $this->availability->blockForBooking($booking);

                return $booking;
            });
        } catch (QueryException $e) {
            throw DatesUnavailableException::isExclusionViolation($e) ? DatesUnavailableException::from($e) : $e;
        }

        $this->availabilityService->flushApartment($apartment->id);

        return $booking;
    }

    /** Charge ce qu'affiche la page de suivi du voyageur (logement et photos). */
    public function forTracking(Booking $booking): Booking
    {
        return $booking->load('apartment.media');
    }

    /**
     * Expire les réservations en attente non payées à temps et libère leurs nuits.
     *
     * @return int nombre de réservations expirées
     */
    public function expireOverdue(): int
    {
        $overdue = $this->bookings->overduePending(now());

        foreach ($overdue as $booking) {
            DB::transaction(function () use ($booking): void {
                $this->bookings->update($booking, ['status' => BookingStatus::Expired]);
                $this->availability->releaseBooking($booking);
            });
            $this->availabilityService->flushApartment($booking->apartment_id);
        }

        return $overdue->count();
    }
}
