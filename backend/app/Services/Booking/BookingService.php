<?php

namespace App\Services\Booking;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Exceptions\Booking\StayNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Availability\AvailabilityService;
use App\Services\Availability\BlockConflictService;
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
 * prend les mêmes nuits ; le battement de ménage, qu'elle ne couvre pas, est revérifié
 * sous un verrou par logement. Sans paiement dans les 40 minutes, la réservation expire
 * et libère les nuits (tâche planifiée chaque minute).
 *
 * Confirmation et expiration ne touchent qu'une réservation encore « pending » en base
 * (mise à jour conditionnelle) : si le webhook de paiement et la tâche d'expiration
 * tombent au même instant, une seule des deux l'emporte.
 */
class BookingService
{
    /**
     * Délai pour payer avant que les nuits soient libérées. Plus long que la session
     * Stripe Checkout (30 minutes, le minimum accepté par Stripe) : les nuits ne sont
     * jamais libérées tant que le voyageur peut encore payer.
     */
    public const HOLD_MINUTES = 40;

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly BlockConflictService $conflicts,
        private readonly QuoteService $quotes,
        private readonly StayRules $rules,
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
                $this->availability->lockApartment($apartment->id);
                if (! $this->rules->isFree($apartment, $stay)) {
                    throw DatesUnavailableException::taken();
                }

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

    /**
     * Confirme une réservation en attente : les nuits restent bloquées pour de bon, et le
     * voyageur comme l'hôte reçoivent la confirmation avec le PDF. Appelée par le webhook
     * de paiement (jalon 9) ; sans effet sur une réservation qui n'est plus « pending » en
     * base (déjà confirmée, ou expirée entre-temps), pour qu'un événement reçu deux fois
     * n'envoie pas deux e-mails et qu'une réservation expirée ne soit jamais confirmée
     * sans ses nuits. Le paiement reçu sur une réservation expirée est traité au jalon 9.
     */
    public function confirm(Booking $booking): Booking
    {
        DB::transaction(function () use ($booking): void {
            $confirmed = $this->bookings->transitionFromPending($booking, [
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
                'expires_at' => null,
            ]);
            if ($confirmed) {
                BookingConfirmed::dispatch($booking);
            }
        });

        return $booking;
    }

    /** Une confirmation (e-mail, PDF) existe pour les réservations confirmées ou terminées. */
    public function hasConfirmation(Booking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true);
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
        return $this->bookings->overduePending(now())
            ->filter(fn (Booking $booking) => $this->expire($booking))
            ->count();
    }

    /**
     * Expire une réservation en attente et libère ses nuits : délai de paiement dépassé,
     * ou nuits prises entre-temps chez une plateforme. Les conflits que ces nuits
     * portaient sont levés. Renvoie false si elle n'était plus en attente (confirmée
     * entre-temps par le paiement : ses nuits restent bloquées).
     * Jalon 9 : ferme aussi la session Stripe Checkout encore ouverte.
     */
    public function expire(Booking $booking): bool
    {
        $released = DB::transaction(function () use ($booking): bool {
            if (! $this->bookings->transitionFromPending($booking, ['status' => BookingStatus::Expired])) {
                return false;
            }
            $this->availability->releaseBooking($booking);
            $this->conflicts->release($booking->apartment_id, $booking->arrival, $booking->departure);

            return true;
        });

        if ($released) {
            $this->availabilityService->flushApartment($booking->apartment_id);
        }

        return $released;
    }
}
