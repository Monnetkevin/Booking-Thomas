<?php

namespace App\Services\Booking;

use App\Enums\AuditAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Exceptions\Booking\StayNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Availability\AvailabilityService;
use App\Services\Pricing\MissingRateException;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Réservation saisie par l'hôte (téléphone, connaissance), spec « Réservation manuelle » :
 * confirmée tout de suite, sans paiement en ligne ni étape de validation. Elle respecte
 * l'occupation des nuits et la capacité du logement, mais ni le préavis ni le séjour
 * minimum. Le prix est calculé par PriceCalculator ; l'hôte peut fixer un autre total
 * (écart noté comme ajustement), ou le prix convenu si aucun tarif ne couvre les nuits.
 * Un règlement reçu hors ligne est noté comme paiement réussi.
 */
class ManualBookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly PriceCalculator $calculator,
        private readonly CancellationTerms $cancellation,
        private readonly BookingReferenceGenerator $references,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, email: string|null, phone: string|null, country: string|null, locale: string, housekeeping_notes: string|null}  $guest
     * @param  int|null  $total  total fixé par l'hôte, en centimes (null : prix calculé)
     *
     * @throws StayNotAllowedException trop de voyageurs pour le logement
     * @throws ValidationException aucun tarif et aucun total
     * @throws DatesUnavailableException nuits déjà occupées
     */
    public function create(Apartment $apartment, Stay $stay, array $guest, ?int $total, bool $paid, bool $notifyGuest): Booking
    {
        if ($stay->guests() > $apartment->capacity) {
            throw new StayNotAllowedException([['code' => 'too_many_guests', 'message' => __('stay.too_many_guests', ['capacity' => $apartment->capacity])]]);
        }
        $amounts = $this->amounts($apartment, $stay, $total);

        try {
            $booking = DB::transaction(function () use ($apartment, $stay, $guest, $amounts, $paid, $notifyGuest): Booking {
                $this->availability->lockApartment($apartment->id);
                if ($this->availability->hasActiveBlock($apartment->id, $stay->arrival->toDateString(), $stay->departure->toDateString())) {
                    throw DatesUnavailableException::taken();
                }

                $booking = $this->bookings->create([
                    'reference' => $this->references->generate(),
                    'apartment_id' => $apartment->id,
                    'source' => BookingSource::Manual,
                    'status' => BookingStatus::Confirmed,
                    'guest_name' => $guest['name'],
                    'guest_email' => $guest['email'],
                    'guest_phone' => $guest['phone'],
                    'guest_country' => $guest['country'],
                    'locale' => $guest['locale'],
                    'housekeeping_notes' => $guest['housekeeping_notes'],
                    'adults' => $stay->adults,
                    'children' => $stay->children,
                    'arrival' => $stay->arrival->toDateString(),
                    'departure' => $stay->departure->toDateString(),
                    ...$amounts,
                    'cancellation_policy' => $this->cancellation->snapshot($apartment->cancellation_policy),
                    'confirmed_at' => now(),
                ]);
                $this->availability->blockForBooking($booking);
                if ($paid && $booking->total_amount > 0) {
                    $this->bookings->recordOfflinePayment($booking, $booking->total_amount);
                }
                BookingConfirmed::dispatch($booking, $notifyGuest);

                return $booking;
            });
        } catch (QueryException $e) {
            throw DatesUnavailableException::isExclusionViolation($e) ? DatesUnavailableException::from($e) : $e;
        }

        $this->availabilityService->flushApartment($apartment->id);
        $this->audit->record(AuditAction::BookingCreated, $booking, after: [
            'reference' => $booking->reference,
            'apartment_id' => $apartment->id,
            'arrival' => $stay->arrival->toDateString(),
            'departure' => $stay->departure->toDateString(),
            'total_amount' => $booking->total_amount,
            'adjusted' => $total !== null,
            'paid_offline' => $paid,
        ]);

        return $booking;
    }

    /**
     * Montants figés de la réservation. Un total plus bas que le prix calculé est une
     * remise ; plus haut, un supplément d'hébergement.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function amounts(Apartment $apartment, Stay $stay, ?int $total): array
    {
        try {
            $quote = $this->calculator->calculate($apartment, $stay);
        } catch (MissingRateException) {
            if ($total === null) {
                throw ValidationException::withMessages(['total_amount' => __('booking.errors.no_rate', [], 'fr')]);
            }

            return $this->agreed($total);
        }

        $total ??= $quote->total;
        $difference = $total - $quote->total;
        $lines = $quote->lines;
        if ($difference !== 0) {
            $lines[] = ['type' => 'adjustment', 'label' => __('pricing.host_adjustment', [], 'fr'), 'amount' => $difference];
        }

        return [
            'accommodation_amount' => $quote->accommodation + max(0, $difference),
            'cleaning_fee' => $quote->cleaningFee,
            'tourist_tax' => $quote->touristTax,
            'discount_amount' => max(0, -$difference),
            'total_amount' => $total,
            'price_breakdown' => [...$quote->toArray(), 'lines' => $lines, 'total' => $total],
        ];
    }

    /**
     * Prix convenu, sans tarif pour ces nuits : tout compte comme hébergement.
     *
     * @return array<string, mixed>
     */
    private function agreed(int $total): array
    {
        $line = ['type' => 'agreed', 'label' => __('pricing.agreed_price', [], 'fr'), 'amount' => $total];

        return [
            'accommodation_amount' => $total,
            'cleaning_fee' => 0,
            'tourist_tax' => 0,
            'discount_amount' => 0,
            'total_amount' => $total,
            'price_breakdown' => ['nights' => [], 'accommodation' => $total, 'cleaning_fee' => 0, 'tourist_tax' => 0, 'lines' => [$line], 'total' => $total, 'currency' => 'EUR'],
        ];
    }
}
