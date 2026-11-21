<?php

namespace App\Services\Booking;

use App\Enums\AuditAction;
use App\Enums\BookingPaymentState;
use App\Enums\BookingStatus;
use App\Events\StayScheduleChanged;
use App\Exceptions\Booking\BookingNotCancellableException;
use App\Exceptions\Booking\BookingNotConfirmedException;
use App\Exceptions\Booking\GuestEmailMissingException;
use App\Mail\BookingCancelledGuest;
use App\Mail\BookingConfirmedGuest;
use App\Models\Booking;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Availability\AvailabilityService;
use App\Services\Availability\BlockConflictService;
use App\Services\Export\CsvWriter;
use App\Services\Housekeeping\StayChange;
use App\Services\Notifications\HostNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Réservations vues par l'hôte : liste filtrée, fiche, coordonnées du voyageur,
 * annulation et renvoi de la confirmation.
 *
 * Annuler libère les nuits, lève les conflits qu'elles portaient, prévient le ménage et,
 * si l'hôte le veut, le voyageur. Le remboursement proposé suit le barème copié dans la
 * réservation ; il est versé depuis Stripe au jalon 11 (en attendant, hors ligne).
 */
class AdminBookingService
{
    public const PER_PAGE = 25;

    public const EXPORT_LIMIT = 5000;

    /** Champs du voyageur que l'hôte peut corriger. */
    private const GUEST_FIELDS = ['guest_name', 'guest_email', 'guest_phone', 'guest_country', 'housekeeping_notes'];

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly AvailabilityService $availabilityService,
        private readonly BlockConflictService $conflicts,
        private readonly RefundCalculator $refunds,
        private readonly HostNotifier $host,
        private readonly Mailer $mailer,
        private readonly AuditLogger $audit,
        private readonly CsvWriter $csv,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function list(BookingFilters $filters): LengthAwarePaginator
    {
        $page = $this->bookings->paginateForAdmin($filters, self::PER_PAGE);
        // État du règlement, à partir des sommes payées et remboursées chargées par la liste.
        foreach ($page->items() as $booking) {
            $booking->setAttribute(
                'payment_state',
                BookingPaymentState::of($booking->status, (int) $booking->getAttribute('paid_amount'), (int) $booking->getAttribute('refunded_amount')),
            );
        }

        return $page;
    }

    /**
     * Compteurs des onglets de la liste, avec les autres filtres.
     *
     * @return array<string, int>
     */
    public function stageCounts(BookingFilters $filters): array
    {
        return $this->bookings->countByStage($filters);
    }

    /**
     * Liste filtrée en CSV (au plus EXPORT_LIMIT lignes) : ni coordonnées ni notes, seulement
     * ce qui sert à un suivi dans un tableur.
     */
    public function csv(BookingFilters $filters): string
    {
        $rows = [['Référence', 'Voyageur', 'Logement', 'Arrivée', 'Départ', 'Nuits', 'Voyageurs', 'Source', 'Statut', 'Total (€)', 'Taxe de séjour (€)']];
        foreach ($this->bookings->allForAdmin($filters, self::EXPORT_LIMIT) as $booking) {
            $rows[] = [
                $booking->reference,
                $booking->guest_name,
                $booking->apartment?->getTranslation('name', 'fr') ?? '',
                $booking->arrival->toDateString(),
                $booking->departure->toDateString(),
                $booking->nights(),
                $booking->adults + $booking->children,
                __('booking.sources.'.$booking->source->value, [], 'fr'),
                __('booking.statuses.'.$booking->status->value, [], 'fr'),
                $this->csv->euros($booking->total_amount),
                $this->csv->euros($booking->tourist_tax),
            ];
        }

        // Export de données personnelles (noms) : tracé, avec les filtres et le nombre de lignes.
        $this->audit->record(AuditAction::BookingsExported, after: [
            'rows' => count($rows) - 1,
            // Filtres sans la recherche saisie (elle peut contenir un nom) : seulement sa présence.
            'filters' => array_filter([
                'stage' => $filters->stage?->value,
                'status' => $filters->status?->value,
                'source' => $filters->source?->value,
                'apartment_id' => $filters->apartmentId,
                'from' => $filters->from,
                'to' => $filters->to,
                'search' => $filters->search !== null,
            ]),
        ]);

        return $this->csv->write($rows);
    }

    public function details(Booking $booking): Booking
    {
        return $this->bookings->loadDetails($booking);
    }

    /** Remboursement proposé si l'on annule aujourd'hui ; null si la réservation ne s'annule pas. */
    public function refundProposal(Booking $booking): ?RefundProposal
    {
        return $this->cancellable($booking) ? $this->refunds->propose($booking, CarbonImmutable::instance(Date::today())) : null;
    }

    public function cancellable(Booking $booking): bool
    {
        return $booking->status === BookingStatus::Confirmed;
    }

    /**
     * Coordonnées et notes de ménage. Le journal garde quels champs ont changé, pas les
     * coordonnées elles-mêmes (données personnelles), sauf les notes de ménage.
     *
     * @param  array<string, string|null>  $data
     */
    public function update(Booking $booking, array $data): Booking
    {
        $data = array_intersect_key($data, array_flip(self::GUEST_FIELDS));
        $changed = array_keys(array_filter($data, fn (mixed $value, string $field) => $booking->{$field} !== $value, ARRAY_FILTER_USE_BOTH));
        if ($changed === []) {
            return $this->details($booking);
        }

        $notesBefore = $booking->housekeeping_notes;
        $this->bookings->update($booking, $data);
        $this->audit->record(AuditAction::BookingUpdated, $booking,
            ['fields' => $changed, ...(in_array('housekeeping_notes', $changed, true) ? ['housekeeping_notes' => $notesBefore] : [])],
            in_array('housekeeping_notes', $changed, true) ? ['housekeeping_notes' => $booking->housekeeping_notes] : null,
        );

        return $this->details($booking);
    }

    /**
     * @throws BookingNotCancellableException réservation qui n'est pas (ou plus) confirmée
     */
    public function cancel(Booking $booking, bool $notifyGuest): Booking
    {
        if (! $this->cancellable($booking)) {
            throw new BookingNotCancellableException;
        }
        $proposal = $this->refunds->propose($this->bookings->loadDetails($booking), CarbonImmutable::instance(Date::today()));

        $cancelled = DB::transaction(function () use ($booking): bool {
            $this->availability->lockApartment($booking->apartment_id);
            $block = $this->availability->bookingBlock($booking);
            if (! $this->bookings->cancelIfConfirmed($booking)) {
                return false;
            }
            $this->availability->releaseBooking($booking);
            $this->conflicts->release($booking->apartment_id, $booking->arrival, $booking->departure);
            if ($block !== null) {
                StayScheduleChanged::dispatch(StayChange::cancelled($block));
            }

            return true;
        });
        if (! $cancelled) {
            throw new BookingNotCancellableException;
        }

        $this->availabilityService->flushApartment($booking->apartment_id);
        $this->audit->record(AuditAction::BookingCancelled, $booking,
            ['status' => 'confirmed'],
            ['status' => 'cancelled', 'refund_proposed' => $proposal->amount(), 'guest_notified' => $notifyGuest && filled($booking->guest_email)],
        );
        if ($notifyGuest && filled($booking->guest_email)) {
            $this->mailer->to([['email' => $booking->guest_email, 'name' => $booking->guest_name]])
                ->queue(new BookingCancelledGuest($booking, $this->host->recipients()[0] ?? null));
        }

        return $this->details($booking);
    }

    /**
     * @throws BookingNotConfirmedException
     * @throws GuestEmailMissingException
     */
    public function resendConfirmation(Booking $booking): void
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            throw new BookingNotConfirmedException;
        }
        if (blank($booking->guest_email)) {
            throw new GuestEmailMissingException;
        }

        $this->mailer->to([['email' => $booking->guest_email, 'name' => $booking->guest_name]])
            ->queue(new BookingConfirmedGuest($booking, $this->host->recipients()[0] ?? null));
    }
}
