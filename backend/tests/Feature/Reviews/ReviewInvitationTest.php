<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Mail\ReviewInvitationGuest;
use App\Models\Apartment;
use App\Models\Review;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Mail::fake();
    $this->apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
});

it('termine les séjours passés et invite le lendemain du départ, avec un lien à usage unique', function () {
    $yesterday = directBooking($this->apartment, '2026-11-16', '2026-11-19');
    $leavingToday = directBooking($this->apartment, '2026-11-19', '2026-11-20');
    $pending = directBooking($this->apartment, '2026-11-10', '2026-11-12', BookingStatus::Pending);

    $this->artisan('reviews:invite')->expectsOutputToContain('1 séjour(s) terminé(s), 1 invitation(s)')->assertSuccessful();

    expect($yesterday->refresh()->status)->toBe(BookingStatus::Completed)
        ->and($leavingToday->refresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($pending->refresh()->status)->toBe(BookingStatus::Pending);

    $review = Review::query()->sole();
    expect($review->booking_id)->toBe($yesterday->id)
        ->and($review->status)->toBe(ReviewStatus::Invited)
        ->and($review->expires_at->toDateString())->toBe('2026-12-20');

    Mail::assertQueued(ReviewInvitationGuest::class, function (ReviewInvitationGuest $mail) use ($yesterday, $review) {
        preg_match('#/avis/([A-Za-z0-9]{48})#', $mail->render(), $match);

        return $mail->hasTo($yesterday->guest_email) && hash('sha256', $match[1] ?? '') === $review->token_hash;
    });
});

it('n’invite qu’une fois, rattrape une semaine manquée et ignore les séjours sans e-mail', function () {
    directBooking($this->apartment, '2026-11-10', '2026-11-14');
    directBooking($this->apartment, '2026-11-01', '2026-11-05');
    $manual = directBooking($this->apartment, '2026-11-15', '2026-11-18');
    $manual->update(['source' => BookingSource::Manual, 'guest_email' => null]);

    $this->artisan('reviews:invite')->assertSuccessful();
    $this->artisan('reviews:invite')->assertSuccessful();

    // Parti il y a 6 jours : rattrapé ; il y a 15 jours : trop ancien ; sans e-mail : rien.
    Mail::assertQueuedCount(1);
    expect(Review::query()->count())->toBe(1);
});

it('écrit l’invitation dans la langue du voyageur', function () {
    $booking = directBooking($this->apartment, '2026-11-16', '2026-11-19');
    $booking->update(['locale' => 'en']);

    $this->artisan('reviews:invite')->assertSuccessful();

    Mail::assertQueued(ReviewInvitationGuest::class, fn (ReviewInvitationGuest $mail) => $mail->locale === 'en'
        && str_contains($mail->render(), '/en/avis/') && str_contains($mail->render(), 'Leave a review'));
});
