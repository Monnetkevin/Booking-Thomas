<?php

namespace App\Services\Booking;

use App\Enums\AuditAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Exceptions\Apartment\ApartmentNotFoundException;
use App\Exceptions\Booking\FreeStayException;
use App\Exceptions\Booking\NoRateForStayException;
use App\Exceptions\Booking\StayNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Exceptions\Extra\ExtraNotAvailableException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Availability\AvailabilityService;
use App\Services\Extra\ExtraChoice;
use App\Services\Extra\ExtraSelector;
use App\Services\Extra\SelectedExtra;
use App\Services\Pricing\MissingRateException;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Réservation saisie par l'hôte (téléphone, connaissance), spec « Réservation manuelle » :
 * confirmée tout de suite, sans paiement en ligne ni étape de validation. Elle respecte
 * l'occupation des nuits et la capacité du logement, mais ni le préavis, ni le séjour
 * minimum, ni le battement de ménage (turnover_days) : l'hôte peut enchaîner deux séjours
 * le même jour s'il le décide. Le prix est calculé par PriceCalculator ; l'hôte peut fixer un autre total
 * (écart noté comme ajustement), ou le prix convenu si aucun tarif ne couvre les nuits.
 * Un règlement reçu hors ligne est noté comme paiement réussi. Une réservation a toujours
 * un montant : un séjour sans montant se saisit comme une fermeture de dates. Les options
 * choisies par l'hôte ne sont pas tenues au délai de commande ; avec un prix convenu, elles
 * sont comprises dans ce prix et restent notées pour être préparées.
 */
class ManualBookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly PriceCalculator $calculator,
        private readonly CancellationTerms $cancellation,
        private readonly BookingReferenceGenerator $references,
        private readonly AuditLogger $audit,
        private readonly ExtraSelector $extraSelector,
        private readonly ExtraRepositoryInterface $extras,
    ) {}

    /**
     * @param  array{name: string, email: string|null, phone: string|null, country: string|null, locale: string, housekeeping_notes: string|null}  $guest
     * @param  int|null  $total  total fixé par l'hôte, en centimes (null : prix calculé)
     * @param  list<ExtraChoice>  $choices  options choisies
     *
     * @throws ExtraNotAvailableException option inconnue, désactivée ou pas proposée dans ce logement
     * @throws ApartmentNotFoundException logement inconnu ou supprimé
     * @throws StayNotAllowedException trop de voyageurs pour le logement
     * @throws NoRateForStayException aucun tarif et aucun total
     * @throws FreeStayException total à 0 €
     * @throws DatesUnavailableException nuits déjà occupées
     */
    public function create(int $apartmentId, Stay $stay, array $guest, ?int $total, bool $paid, bool $notifyGuest, array $choices = []): Booking
    {
        $apartment = $this->apartments->findWithTrashed($apartmentId);
        if ($apartment === null || $apartment->trashed()) {
            throw new ApartmentNotFoundException;
        }

        if ($stay->guests() > $apartment->capacity) {
            throw new StayNotAllowedException([['code' => 'too_many_guests', 'message' => __('stay.too_many_guests', ['capacity' => $apartment->capacity])]]);
        }
        $selected = $this->extraSelector->select($apartment, $stay, $choices, enforceLeadTime: false);
        [$amounts, $extraItems] = $this->amounts($apartment, $stay, $total, $selected);
        if ($amounts['total_amount'] <= 0) {
            throw new FreeStayException;
        }

        try {
            $booking = DB::transaction(function () use ($apartment, $stay, $guest, $amounts, $extraItems, $paid, $notifyGuest): Booking {
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
                $this->extras->recordForBooking($booking, $extraItems);
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
     * Montants figés de la réservation et options à figer. Un total plus bas que le prix
     * calculé est une remise ; plus haut, un supplément d'hébergement.
     *
     * @param  list<SelectedExtra>  $extras
     * @return array{0: array<string, mixed>, 1: list<array{extra_id: int, name: string, pricing: string, quantity: int, unit_amount: int, amount: int}>}
     *
     * @throws NoRateForStayException
     */
    private function amounts(Apartment $apartment, Stay $stay, ?int $total, array $extras): array
    {
        try {
            $quote = $this->calculator->calculate($apartment, $stay, $extras);
        } catch (MissingRateException) {
            if ($total === null) {
                throw new NoRateForStayException;
            }

            return [$this->agreed($total), $this->calculator->extraItems($stay, $extras)];
        }

        $total ??= $quote->total;
        $difference = $total - $quote->total;
        $lines = $quote->lines;
        if ($difference !== 0) {
            $lines[] = ['type' => 'adjustment', 'label' => __('pricing.host_adjustment', [], 'fr'), 'amount' => $difference];
        }

        // Une remise porte d'abord sur l'hébergement, puis sur le ménage, les options, enfin sur
        // la taxe de séjour : chaque montant figé reste positif et la somme vaut toujours le
        // total. Un ménage ou une taxe offerts ne sont ni comptés en revenu ni remboursés.
        $reduction = max(0, -$difference);
        $discount = min($reduction, $quote->accommodation);
        $cleaningWaived = min($reduction - $discount, $quote->cleaningFee);
        $extrasWaived = min($reduction - $discount - $cleaningWaived, $quote->extras);
        $taxWaived = min($reduction - $discount - $cleaningWaived - $extrasWaived, $quote->touristTax);
        $cleaningFee = $quote->cleaningFee - $cleaningWaived;
        $touristTax = $quote->touristTax - $taxWaived;

        return [[
            'accommodation_amount' => $quote->accommodation + max(0, $difference),
            'cleaning_fee' => $cleaningFee,
            'extras_amount' => $quote->extras - $extrasWaived,
            'tourist_tax' => $touristTax,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'price_breakdown' => [...$quote->toArray(), 'cleaning_fee' => $cleaningFee, 'tourist_tax' => $touristTax, 'lines' => $lines, 'total' => $total],
        ], $quote->extraItems];
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
