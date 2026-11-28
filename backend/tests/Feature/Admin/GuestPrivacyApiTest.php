<?php

use App\Enums\AuditAction;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-28 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
});

/** Séjour terminé de Camille, avec un avis publié et un message de contact. */
function camilleStay(Apartment $apartment, string $arrival = '2026-10-01', string $departure = '2026-10-04'): Booking
{
    return Booking::factory()->for($apartment)->create([
        'status' => BookingStatus::Completed, 'arrival' => $arrival, 'departure' => $departure,
        'guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com', 'guest_phone' => '+33 6 12 34 56 78',
        'guest_message' => 'Arrivée tardive', 'housekeeping_notes' => 'Lit bébé',
    ]);
}

it('exporte toutes les données liées à l’e-mail du voyageur', function () {
    $stay = camilleStay($this->apartment);
    Booking::factory()->for($this->apartment)->create(['guest_email' => 'CAMILLE@example.com', 'arrival' => '2027-02-01', 'departure' => '2027-02-03']);
    Booking::factory()->for($this->apartment)->create(['guest_email' => 'autre@example.com']);
    Review::factory()->published()->create(['booking_id' => $stay->id]);
    ContactMessage::query()->create(['name' => 'Camille', 'email' => 'camille@example.com', 'message' => 'Parking ?', 'locale' => 'fr']);

    $response = $this->get("/api/admin/bookings/{$stay->reference}/guest-data")
        ->assertOk()
        ->assertHeader('Content-Disposition', "attachment; filename=\"donnees-voyageur-{$stay->reference}.json\"");

    $response->assertJsonCount(2, 'bookings')
        ->assertJsonPath('bookings.0.guest.phone', '+33 6 12 34 56 78')
        ->assertJsonPath('bookings.0.apartment', 'Le Cocon')
        ->assertJsonPath('bookings.0.review.display_name', 'Camille')
        ->assertJsonPath('contact_messages.0.message', 'Parking ?');

    $log = AuditLog::query()->where('action', AuditAction::GuestDataExported)->sole();
    expect($log->after)->toBe(['bookings' => 2, 'contact_messages' => 1]);
});

it('anonymise le voyageur : coordonnées effacées, montants et avis gardés', function () {
    $stay = camilleStay($this->apartment);
    $review = Review::factory()->published()->create(['booking_id' => $stay->id]);
    ContactMessage::query()->create(['name' => 'Camille', 'email' => 'camille@example.com', 'message' => 'Parking ?', 'locale' => 'fr']);
    $other = ContactMessage::query()->create(['name' => 'Alex', 'email' => 'alex@example.com', 'message' => 'Bonjour', 'locale' => 'fr']);

    $this->postJson("/api/admin/bookings/{$stay->reference}/anonymize")
        ->assertOk()
        ->assertJsonPath('data.guest_name', 'Voyageur anonymisé')
        ->assertJsonPath('data.anonymized_at', fn ($value) => $value !== null);

    $stay->refresh();
    expect($stay->guest_email)->toBeNull()
        ->and($stay->guest_phone)->toBeNull()
        ->and($stay->guest_message)->toBeNull()
        ->and($stay->housekeeping_notes)->toBeNull()
        ->and($stay->total_amount)->toBe(28600)
        ->and($stay->guest_country)->toBe('FR')
        ->and($review->refresh()->display_name)->toBeNull()
        ->and($review->comment)->toBe('Séjour parfait.')
        ->and(ContactMessage::query()->pluck('id')->all())->toBe([$other->id]);

    $log = AuditLog::query()->where('action', AuditAction::GuestAnonymized)->sole();
    expect($log->after)->toBe(['bookings' => [$stay->reference]]);
});

it('refuse d’anonymiser un voyageur dont un séjour n’est pas terminé', function () {
    $stay = camilleStay($this->apartment);
    Booking::factory()->for($this->apartment)->create(['guest_email' => 'camille@example.com', 'arrival' => '2026-11-27', 'departure' => '2026-11-30']);

    $this->postJson("/api/admin/bookings/{$stay->reference}/anonymize")
        ->assertStatus(422)
        ->assertJsonPath('code', 'guest_stay_in_progress');

    expect($stay->refresh()->guest_email)->toBe('camille@example.com');
});

it('réserve l’export et l’anonymisation à l’administrateur', function () {
    $stay = camilleStay($this->apartment);
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson("/api/admin/bookings/{$stay->reference}/guest-data")->assertForbidden();
    $this->postJson("/api/admin/bookings/{$stay->reference}/anonymize")->assertForbidden();
});

it('anonymise chaque mois les voyageurs partis depuis plus de 3 ans', function () {
    $old = camilleStay($this->apartment, '2023-11-20', '2023-11-27');
    $recent = camilleStay($this->apartment, '2023-11-25', '2023-11-29');

    Artisan::call('privacy:anonymize');

    expect($old->refresh()->guest_name)->toBe('Voyageur anonymisé')
        ->and($old->anonymized_at)->not->toBeNull()
        ->and($recent->refresh()->guest_email)->toBe('camille@example.com');
});

it('supprime les messages de contact au bout d’un an', function () {
    $old = ContactMessage::query()->create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'Ancien', 'locale' => 'fr']);
    $old->forceFill(['created_at' => now()->subMonths(13)])->save();
    $recent = ContactMessage::query()->create(['name' => 'B', 'email' => 'b@example.com', 'message' => 'Récent', 'locale' => 'fr']);

    Artisan::call('model:prune', ['--model' => [ContactMessage::class]]);

    expect(ContactMessage::query()->pluck('id')->all())->toBe([$recent->id]);
});
