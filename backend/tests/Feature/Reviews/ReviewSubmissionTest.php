<?php

use App\Enums\ReviewStatus;
use App\Models\Apartment;
use App\Models\Review;
use App\Models\User;
use App\Notifications\Host\ReviewSubmittedNotification;
use App\Services\Review\ReviewInvitationService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Notification::fake();
    $this->host = User::factory()->create();
    $apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
    $this->booking = directBooking($apartment, '2026-11-16', '2026-11-19');
    $this->booking->update(['guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com']);
    $this->token = Str::random(48);
    $this->review = Review::factory()->create([
        'booking_id' => $this->booking->id,
        'token_hash' => ReviewInvitationService::hash($this->token),
        'expires_at' => now()->addDays(30),
    ]);
});

function reviewPayload(array $overrides = []): array
{
    return [...['rating' => 4, 'comment' => 'Très bel appartement, bien placé.', 'display_name' => 'Camille', 'consent' => true], ...$overrides];
}

it('montre au voyageur son séjour et son prénom, sans autre coordonnée', function () {
    $response = $this->getJson("/api/review-invitations/{$this->token}")->assertOk()
        ->assertJsonPath('data', [
            'apartment' => 'Le Cocon', 'arrival' => '2026-11-16', 'departure' => '2026-11-19', 'nights' => 3,
            'first_name' => 'Camille', 'expires_on' => '2026-12-20',
        ]);

    expect($response->getContent())->not->toContain('camille@example.com')->not->toContain('Martin');
    $this->getJson("/api/review-invitations/{$this->token}", ['Accept-Language' => 'en'])->assertJsonPath('data.apartment', 'The Cocoon');
});

it('dépose l’avis en attente de modération et prévient l’hôte', function () {
    $this->postJson("/api/review-invitations/{$this->token}", reviewPayload())->assertCreated();

    $review = $this->review->refresh();
    expect($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->rating)->toBe(4)
        ->and($review->display_name)->toBe('Camille')
        ->and($review->submitted_at)->not->toBeNull();
    Notification::assertSentTo($this->host, ReviewSubmittedNotification::class, fn (ReviewSubmittedNotification $notification) => $notification->title() === 'Avis à modérer : Le Cocon'
        && $notification->body() === 'Camille, 4/5 : « Très bel appartement, bien placé. »'
        && $notification->link() === '/admin/avis');
});

it('ne sert qu’une fois', function () {
    $this->postJson("/api/review-invitations/{$this->token}", reviewPayload())->assertCreated();

    $this->postJson("/api/review-invitations/{$this->token}", reviewPayload(['rating' => 1]))->assertStatus(410)->assertJsonPath('code', 'review_link_invalid');
    $this->getJson("/api/review-invitations/{$this->token}")->assertStatus(410);
    expect($this->review->refresh()->rating)->toBe(4);
});

it('refuse un lien expiré ou inconnu, avec la même erreur', function () {
    Date::setTestNow('2026-12-20 10:00:01');

    $this->getJson("/api/review-invitations/{$this->token}")->assertStatus(410)->assertJsonPath('code', 'review_link_invalid');
    $this->getJson('/api/review-invitations/'.Str::random(48))->assertStatus(410)->assertJsonPath('code', 'review_link_invalid');
    $this->getJson('/api/review-invitations/court')->assertNotFound();
});

it('valide la note, le commentaire, le prénom et l’accord de publication', function () {
    $this->postJson("/api/review-invitations/{$this->token}", reviewPayload(['rating' => 6, 'comment' => 'Bien', 'display_name' => '', 'consent' => false]))
        ->assertJsonValidationErrors(['rating', 'comment', 'display_name', 'consent']);

    expect($this->review->refresh()->status)->toBe(ReviewStatus::Invited);
});
