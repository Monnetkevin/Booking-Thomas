<?php

use App\Enums\AuditAction;
use App\Enums\BlockType;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Mail\BookingCancelledGuest;
use App\Mail\BookingConfirmedGuest;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\IcalFeed;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Housekeeping\StayChangedNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Mail::fake();
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
});

/** Réservation confirmée, payée, au barème modéré (100 % jusqu'à 5 jours avant, puis 50 %). */
function paidBooking(Apartment $apartment, string $arrival, string $departure, array $attributes = []): Booking
{
    $booking = directBooking($apartment, $arrival, $departure);
    $booking->update([
        'accommodation_amount' => 30000, 'cleaning_fee' => 4500, 'tourist_tax' => 0, 'total_amount' => 34500,
        'cancellation_policy' => ['policy' => 'moderate', 'tiers' => [['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50]]],
        ...$attributes,
    ]);
    Payment::query()->create(['booking_id' => $booking->id, 'type' => 'payment', 'amount' => 34500, 'status' => PaymentStatus::Succeeded]);

    return $booking->fresh();
}

it('liste les réservations, arrivées les plus proches d’abord, et les filtre', function () {
    $other = Apartment::factory()->create();
    paidBooking($this->apartment, '2026-12-10', '2026-12-12', ['guest_name' => 'Camille Martin']);
    paidBooking($this->apartment, '2026-12-01', '2026-12-04', ['guest_name' => 'Léa Dupont', 'source' => BookingSource::Manual]);
    paidBooking($other, '2027-01-05', '2027-01-08', ['status' => BookingStatus::Cancelled]);

    $this->getJson('/api/admin/bookings')->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.guest_name', 'Léa Dupont')
        ->assertJsonPath('data.0.apartment.name', 'Le Cocon')
        ->assertJsonPath('data.0.nights', 3);
    $this->getJson('/api/admin/bookings?status=cancelled')->assertJsonCount(1, 'data');
    $this->getJson("/api/admin/bookings?apartment_id={$other->id}")->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/bookings?source=manual')->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/bookings?search=camille')->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/bookings?search=100%25')->assertJsonCount(0, 'data');
    $this->getJson('/api/admin/bookings?from=2026-12-03&to=2026-12-11')->assertJsonCount(2, 'data');
    $this->getJson('/api/admin/bookings?status=inconnu')->assertJsonValidationErrors('status');
});

it('montre la fiche, ses paiements et le remboursement proposé selon le barème', function () {
    $early = paidBooking($this->apartment, '2026-12-10', '2026-12-12');
    $late = paidBooking($this->apartment, '2026-11-23', '2026-11-25');

    $this->getJson("/api/admin/bookings/{$early->reference}")->assertOk()
        ->assertJsonPath('data.payments.0.amount', 34500)
        ->assertJsonPath('data.refund_proposal', ['days_before_arrival' => 20, 'percent' => 100, 'by_policy' => 34500, 'paid' => 34500, 'amount' => 34500]);
    // 3 jours avant : 50 % de l'hébergement, ménage rendu en entier.
    $this->getJson("/api/admin/bookings/{$late->reference}")
        ->assertJsonPath('data.refund_proposal.percent', 50)
        ->assertJsonPath('data.refund_proposal.amount', 19500);
});

it('corrige les coordonnées et les notes de ménage, journalisées sans les coordonnées', function () {
    $booking = paidBooking($this->apartment, '2026-12-10', '2026-12-12');

    $this->patchJson("/api/admin/bookings/{$booking->reference}", ['guest_phone' => ' 06 11 22 33 44 ', 'housekeeping_notes' => 'Lit bébé'])
        ->assertOk()
        ->assertJsonPath('data.guest_phone', '06 11 22 33 44')
        ->assertJsonPath('data.housekeeping_notes', 'Lit bébé');

    $log = AuditLog::sole();
    expect($log->action)->toBe(AuditAction::BookingUpdated)
        ->and($log->before['fields'])->toEqualCanonicalizing(['guest_phone', 'housekeeping_notes'])
        ->and(json_encode([$log->before, $log->after]))->not->toContain('06 11');
});

it('annule une réservation : nuits libérées, conflit levé, ménage et voyageur prévenus, journal', function () {
    Notification::fake();
    $cleaner = User::factory()->housekeeping()->create();
    $booking = paidBooking($this->apartment, '2026-11-25', '2026-11-28', ['guest_email' => 'camille@example.com', 'locale' => 'en']);
    $airbnb = AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-26', '2026-11-29')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->apartment)->create()->id, 'external_uid' => 'HM1', 'has_conflict' => true,
    ]);

    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => true])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.refund_proposal', null);

    expect($booking->fresh()->availabilityBlock->is_active)->toBeFalse()
        ->and($airbnb->fresh()->has_conflict)->toBeFalse()
        ->and(AuditLog::sole()->after)->toMatchArray(['status' => 'cancelled', 'refund_proposed' => 34500, 'guest_notified' => true]);
    Notification::assertSentTo($cleaner, StayChangedNotification::class, fn ($n) => $n->change->kind->value === 'cancelled');
    Mail::assertQueued(BookingCancelledGuest::class, fn ($mail) => $mail->hasTo('camille@example.com')
        && str_contains($mail->render(), 'has been cancelled'));

    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => false])
        ->assertUnprocessable()->assertJsonPath('code', 'booking_not_cancellable');
});

it('annule sans prévenir le voyageur, et refuse une réservation en attente de paiement', function () {
    $booking = paidBooking($this->apartment, '2026-12-10', '2026-12-12');
    $pending = directBooking($this->apartment, '2026-12-20', '2026-12-22', BookingStatus::Pending);

    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => false])->assertOk();
    $this->postJson("/api/admin/bookings/{$pending->reference}/cancel", ['notify_guest' => false])->assertUnprocessable();

    Mail::assertNotQueued(BookingCancelledGuest::class);
});

it('renvoie la confirmation et donne le PDF d’une réservation confirmée seulement', function () {
    $booking = paidBooking($this->apartment, '2026-12-10', '2026-12-12', ['guest_email' => 'camille@example.com']);
    $cancelled = paidBooking($this->apartment, '2026-12-20', '2026-12-22', ['status' => BookingStatus::Cancelled]);

    $this->postJson("/api/admin/bookings/{$booking->reference}/resend-confirmation")->assertNoContent();
    Mail::assertQueued(BookingConfirmedGuest::class, fn ($mail) => $mail->hasTo('camille@example.com'));
    $this->postJson("/api/admin/bookings/{$cancelled->reference}/resend-confirmation")->assertJsonPath('code', 'booking_not_confirmed');

    $this->get("/api/admin/bookings/{$booking->reference}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get("/api/admin/bookings/{$cancelled->reference}/pdf")->assertNotFound();
});

it('réserve les réservations à l’administrateur', function () {
    $booking = paidBooking($this->apartment, '2026-12-10', '2026-12-12');
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson('/api/admin/bookings')->assertForbidden();
    $this->getJson("/api/admin/bookings/{$booking->reference}")->assertForbidden();
    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => false])->assertForbidden();
    $this->get("/api/admin/bookings/{$booking->reference}/pdf")->assertForbidden();
});
