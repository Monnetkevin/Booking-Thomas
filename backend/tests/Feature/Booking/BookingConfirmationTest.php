<?php

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\BookingConfirmationPdf;
use App\Services\Booking\BookingLinks;
use App\Services\Booking\BookingService;
use App\Support\Money;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    config(['app.name' => 'Nom du site', 'booking.frontend_url' => 'https://exemple.fr']);
    app(SettingRepositoryInterface::class)->put('notifications.admin_emails', ['hote@example.com']);

    $apartment = Apartment::factory()->create([
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'], 'address' => '12 rue de la Paix',
        'check_in_time' => '16:00:00', 'check_out_time' => '10:00:00',
    ]);
    $this->booking = Booking::factory()->for($apartment)->create([
        'reference' => 'BT-ABCD2345', 'status' => BookingStatus::Pending, 'locale' => 'en',
        'guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com', 'guest_phone' => '+33 6 12 34 56 78',
        'arrival' => '2026-12-18', 'departure' => '2026-12-21', 'children' => 1,
        'expires_at' => now()->addMinutes(30),
        'cancellation_policy' => ['policy' => 'moderate', 'tiers' => [['days_before' => 5, 'refund_percent' => 100]]],
    ]);
});

it('formate les montants dans la langue du lecteur', function () {
    expect(Money::format(31600, 'fr'))->toBe("316,00\u{00A0}€")
        ->and(Money::format(31600, 'en'))->toBe('€316.00');
});

it('confirme la réservation et envoie la confirmation au voyageur et à l’hôte', function () {
    Mail::fake();

    app(BookingService::class)->confirm($this->booking);

    $booking = $this->booking->fresh();
    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull()
        ->and($booking->expires_at)->toBeNull();
    Mail::assertQueued(BookingConfirmedGuest::class, fn ($mail) => $mail->hasTo('camille@example.com') && $mail->locale === 'en');
    Mail::assertQueued(BookingConfirmedHost::class, fn ($mail) => $mail->hasTo('hote@example.com') && $mail->locale === 'fr');
});

it('n’envoie rien de plus si la confirmation arrive deux fois', function () {
    Mail::fake();
    $service = app(BookingService::class);

    $service->confirm($this->booking);
    $service->confirm($this->booking->fresh());

    Mail::assertQueuedCount(2);
});

it('ne confirme pas une réservation expirée', function () {
    Mail::fake();
    $this->booking->update(['status' => BookingStatus::Expired]);

    app(BookingService::class)->confirm($this->booking);

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Expired);
    Mail::assertNothingQueued();
});

it('rédige la confirmation du voyageur dans sa langue, avec son lien de suivi et le PDF', function () {
    $this->booking->update(['status' => BookingStatus::Confirmed]);
    $mail = new BookingConfirmedGuest($this->booking, 'hote@example.com');

    $mail->assertHasSubject('Booking confirmed: The Cocoon (BT-ABCD2345)')
        ->assertSeeInHtml('Your booking is confirmed')
        ->assertSeeInHtml('Fri 18 Dec 2026, from 16:00')
        ->assertSeeInHtml('2 adults, 1 child')
        ->assertSeeInHtml('€286.00')
        ->assertSeeInHtml('https://exemple.fr/en/reservation/BT-ABCD2345?signature=', false)
        ->assertSeeInText('100% refunded up to 5 days before check-in')
        ->assertHasReplyTo('hote@example.com');

    $attachments = $mail->attachments();
    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe('confirmation-BT-ABCD2345.pdf')
        ->and($attachments[0]->mime)->toBe('application/pdf');
});

it('prévient l’hôte en français avec les coordonnées du voyageur', function () {
    $mail = new BookingConfirmedHost($this->booking);

    $mail->assertHasSubject('Nouvelle réservation : Le Cocon, ven. 18 déc. 2026 → lun. 21 déc. 2026')
        ->assertSeeInHtml('+33 6 12 34 56 78')
        ->assertSeeInHtml('286,00')
        ->assertHasReplyTo('camille@example.com', 'Camille Martin');
});

it('produit un PDF de confirmation', function () {
    expect(app(BookingConfirmationPdf::class)->render($this->booking))->toStartWith('%PDF-');
});

it('ne donne le PDF qu’avec un lien signé et pour une réservation confirmée', function () {
    $path = fn () => '/api/bookings/BT-ABCD2345/pdf?signature='.app(BookingLinks::class)->signature('bookings.pdf', $this->booking);

    $this->get($path())->assertNotFound();
    $this->get('/api/bookings/BT-ABCD2345/pdf')->assertForbidden();

    $this->booking->update(['status' => BookingStatus::Confirmed]);
    $this->get($path())
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="confirmation-BT-ABCD2345.pdf"');
});

it('montre le lien du PDF sur la page de suivi une fois la réservation confirmée', function () {
    $show = fn () => $this->getJson('/api/bookings/BT-ABCD2345?signature='.app(BookingLinks::class)->signature('bookings.show', $this->booking));

    $show()->assertJsonPath('data.pdf_url', null);
    $this->booking->update(['status' => BookingStatus::Confirmed]);
    $show()->assertJsonPath('data.pdf_url', fn (string $url) => str_starts_with($url, 'https://exemple.fr/api/bookings/BT-ABCD2345/pdf?signature='));
});

it('confirme une réservation en ligne de commande, en attendant le paiement', function () {
    Mail::fake();

    $this->artisan('bookings:confirm BT-ABCD2345')->assertSuccessful();
    $this->artisan('bookings:confirm BT-INCONNUE')->assertFailed();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});
