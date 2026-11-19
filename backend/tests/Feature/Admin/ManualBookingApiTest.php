<?php

use App\Enums\AuditAction;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\RatePeriod;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Mail::fake();
    app(SettingRepositoryInterface::class)->put('notifications.admin_emails', ['hote@example.com']);
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create(['capacity' => 4, 'cleaning_fee' => 4000, 'min_stay' => 3, 'turnover_days' => 1]);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-12-31', 'nightly_price' => 9000, 'min_stay' => 3, 'weekend_supplement' => 0]);
});

function manualBooking(array $overrides = []): array
{
    return array_merge([
        'apartment_id' => test()->apartment->id,
        'arrival' => '2026-11-21', 'departure' => '2026-11-23',
        'adults' => 2, 'children' => 0,
        'guest_name' => 'Paul Durand', 'guest_email' => 'Paul@Example.com', 'guest_phone' => '06 00 00 00 00',
        'paid' => false, 'send_confirmation' => true,
    ], $overrides);
}

it('crée une réservation confirmée au prix calculé, sans préavis ni séjour minimum', function () {
    $this->postJson('/api/admin/bookings', manualBooking())
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.source', 'manual')
        ->assertJsonPath('data.guest_email', 'paul@example.com')
        ->assertJsonPath('data.total_amount', 22000);

    $booking = Booking::sole();
    expect($booking->availabilityBlock->is_active)->toBeTrue()
        ->and($booking->payments)->toHaveCount(0)
        ->and(AuditLog::sole()->action)->toBe(AuditAction::BookingCreated);
    Mail::assertQueued(BookingConfirmedGuest::class, fn ($mail) => $mail->hasTo('paul@example.com'));
    Mail::assertNotQueued(BookingConfirmedHost::class);
});

it('applique le total fixé par l’hôte et note le règlement reçu hors ligne', function () {
    $this->postJson('/api/admin/bookings', manualBooking(['total_amount' => 20000, 'paid' => true, 'send_confirmation' => false]))
        ->assertCreated()
        ->assertJsonPath('data.total_amount', 20000)
        ->assertJsonPath('data.discount_amount', 2000)
        ->assertJsonPath('data.price_lines.2.label', 'Ajustement de l’hôte')
        ->assertJsonPath('data.price_lines.2.amount', -2000)
        ->assertJsonPath('data.payments.0.amount', 20000)
        ->assertJsonPath('data.payments.0.status', 'succeeded')
        ->assertJsonPath('data.refund_proposal.paid', 20000);

    Mail::assertNothingQueued();
});

it('accepte un prix convenu quand aucun tarif ne couvre les nuits, et un voyageur sans e-mail', function () {
    $this->postJson('/api/admin/bookings', manualBooking(['arrival' => '2027-02-01', 'departure' => '2027-02-04']))
        ->assertJsonValidationErrors('total_amount');

    $this->postJson('/api/admin/bookings', manualBooking([
        'arrival' => '2027-02-01', 'departure' => '2027-02-04', 'total_amount' => 30000, 'guest_email' => '',
    ]))->assertCreated()
        ->assertJsonPath('data.price_lines.0.label', 'Prix convenu')
        ->assertJsonPath('data.guest_email', null);

    Mail::assertNothingQueued();
    $booking = Booking::sole();
    $this->postJson("/api/admin/bookings/{$booking->reference}/resend-confirmation")->assertJsonPath('code', 'guest_email_missing');
});

it('refuse des nuits occupées et trop de voyageurs', function () {
    directBooking($this->apartment, '2026-11-22', '2026-11-25');

    $this->postJson('/api/admin/bookings', manualBooking())->assertStatus(409);
    // Le battement de ménage ne s'applique pas : un départ le jour de l'arrivée suffit.
    $this->postJson('/api/admin/bookings', manualBooking(['arrival' => '2026-11-25', 'departure' => '2026-11-27']))->assertCreated();
    $this->postJson('/api/admin/bookings', manualBooking(['arrival' => '2026-12-10', 'departure' => '2026-12-12', 'adults' => 5]))
        ->assertUnprocessable()->assertJsonPath('code', 'stay_not_allowed');
});

it('refuse une date impossible, une arrivée passée ou plus de 90 nuits, sans erreur serveur', function () {
    $this->postJson('/api/admin/bookings', manualBooking(['arrival' => '2026-13-01']))->assertJsonValidationErrors('arrival');
    $this->postJson('/api/admin/bookings', manualBooking(['arrival' => '2026-11-19', 'departure' => '2026-11-21']))->assertJsonValidationErrors('arrival');
    $this->postJson('/api/admin/bookings', manualBooking(['departure' => '2027-03-01']))->assertJsonValidationErrors('departure');
});

it('valide la saisie et réserve la création à l’administrateur', function () {
    $this->postJson('/api/admin/bookings', manualBooking(['departure' => '2026-11-20', 'guest_email' => 'x', 'apartment_id' => 999999]))
        ->assertJsonValidationErrors(['departure', 'guest_email', 'apartment_id']);

    $this->actingAs(User::factory()->housekeeping()->create())
        ->postJson('/api/admin/bookings', manualBooking())->assertForbidden();
    expect(Booking::query()->where('source', BookingSource::Manual)->where('status', BookingStatus::Confirmed)->count())->toBe(0);
});
