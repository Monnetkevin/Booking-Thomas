<?php

use App\Enums\BookingStatus;
use App\Enums\IcalSyncTrigger;
use App\Models\Apartment;
use App\Models\IcalFeed;
use App\Models\Notification as StoredNotification;
use App\Models\User;
use App\Notifications\Host\BookingConfirmedNotification;
use App\Notifications\Host\ContactMessageNotification;
use App\Notifications\Host\IcalEmptyCalendarRefusedNotification;
use App\Services\Booking\BookingService;
use App\Services\Ical\IcalImportService;
use App\Services\Security\HumanVerifier;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Date::setTestNow('2026-11-19 10:00:00');
    Mail::fake();
    Sleep::fake();
    $this->host = User::factory()->create();
    $this->apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
});

it('enregistre dans le back-office de chaque administrateur actif une réservation confirmée', function () {
    $other = User::factory()->create();
    $inactive = User::factory()->inactive()->create();
    $cleaner = User::factory()->housekeeping()->create();
    $booking = directBooking($this->apartment, '2026-12-18', '2026-12-21', BookingStatus::Pending);
    $booking->update(['guest_name' => 'Camille Martin', 'adults' => 2, 'children' => 1, 'total_amount' => 48500, 'expires_at' => now()->addMinutes(30)]);

    app(BookingService::class)->confirm($booking->fresh());

    $stored = StoredNotification::query()->where('type', 'booking_confirmed')->get();
    expect($stored)->toHaveCount(2)
        ->and($stored->pluck('notifiable_id')->sort()->values()->all())->toBe(collect([$this->host->id, $other->id])->sort()->values()->all())
        ->and($stored->first()->data['title'])->toBe('Nouvelle réservation : Le Cocon')
        ->and($stored->first()->data['body'])->toContain('Camille Martin')->toContain('3 voyageur(s)')->toContain('485,00')
        ->and($inactive->notifications()->count())->toBe(0)
        ->and($cleaner->notifications()->where('type', 'booking_confirmed')->count())->toBe(0);
});

it('signale un message de contact dans le back-office, sans e-mail en plus de celui du réglage', function () {
    Notification::fake();
    app()->instance(HumanVerifier::class, new class implements HumanVerifier
    {
        public function verify(?string $token, ?string $ip): bool
        {
            return true;
        }
    });

    $this->postJson('/api/contact', ['name' => 'Léa', 'email' => 'lea@example.com', 'message' => str_repeat('Bonjour ', 40)])->assertCreated();

    Notification::assertSentTo($this->host, ContactMessageNotification::class, fn ($notification, array $channels) => count($channels) === 1
        && mb_strlen($notification->body()) <= 163);
});

it('signale une seule fois un calendrier vide refusé', function () {
    Notification::fake();
    Http::fake(['www.airbnb.fr/*' => Http::sequence()
        ->push(icalFixture('airbnb'), 200, ['ETag' => '"v1"'])
        ->push(icalFixture('airbnb-empty'), 200, ['ETag' => '"v2"'])
        ->push(icalFixture('airbnb-empty'), 200, ['ETag' => '"v3"'])]);
    $feed = IcalFeed::factory()->for($this->apartment)->create(['import_url' => 'https://www.airbnb.fr/calendar/ical/1.ics']);

    foreach (range(1, 3) as $read) {
        app(IcalImportService::class)->sync($feed->fresh(), IcalSyncTrigger::Manual);
    }

    Notification::assertSentToTimes($this->host, IcalEmptyCalendarRefusedNotification::class, 1);
    Notification::assertSentTo($this->host, IcalEmptyCalendarRefusedNotification::class, fn ($notification, array $channels) => in_array('mail', $channels, true)
        && $notification->link() === '/admin/synchronisation');
});

it('ne notifie personne quand aucun administrateur n’est actif', function () {
    Notification::fake();
    $this->host->update(['is_active' => false]);
    $booking = directBooking($this->apartment, '2026-12-18', '2026-12-21', BookingStatus::Pending);
    $booking->update(['expires_at' => now()->addMinutes(30)]);

    app(BookingService::class)->confirm($booking->fresh());

    Notification::assertNotSentTo($this->host, BookingConfirmedNotification::class);
});
