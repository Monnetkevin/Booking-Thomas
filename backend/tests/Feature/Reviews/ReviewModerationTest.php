<?php

use App\Enums\ApartmentStatus;
use App\Enums\AuditAction;
use App\Enums\ReviewStatus;
use App\Mail\ReviewInvitationGuest;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Mail::fake();
    $this->actingAs(User::factory()->create());
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'], 'slug' => 'le-cocon', 'status' => ApartmentStatus::Published]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'The Nest'], 'slug' => 'le-nid', 'status' => ApartmentStatus::Published]);
});

/** Avis d'un séjour terminé dans ce logement. */
function reviewFor(Apartment $apartment, string $arrival, string $state = 'submitted', int $rating = 5): Review
{
    $booking = directBooking($apartment, $arrival, Date::parse($arrival)->addDays(2)->toDateString());
    $factory = Review::factory()->state(['booking_id' => $booking->id]);

    return match ($state) {
        'invited' => $factory->create(),
        'published' => $factory->published($rating)->create(),
        default => $factory->submitted($rating)->create(),
    };
}

describe('modération', function () {
    it('liste la file par statut et par logement, avec les nombres', function () {
        reviewFor($this->cocon, '2026-10-01');
        reviewFor($this->nid, '2026-10-10');
        reviewFor($this->cocon, '2026-09-01', 'published');
        reviewFor($this->cocon, '2026-08-01', 'invited');

        $this->getJson('/api/admin/reviews?status=pending')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.booking.guest_name', fn ($name) => is_string($name))
            ->assertJsonPath('meta.counts', ['invited' => 1, 'pending' => 2, 'published' => 1, 'rejected' => 0]);
        $this->getJson("/api/admin/reviews?status=pending&apartment_id={$this->nid->id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.apartment.name', 'Le Nid')
            ->assertJsonPath('meta.counts.pending', 1);
    });

    it('publie, refuse avec un motif interne puis republie, et trace chaque décision', function () {
        $review = reviewFor($this->cocon, '2026-10-01');

        $this->postJson("/api/admin/reviews/{$review->id}/publish")->assertOk()->assertJsonPath('data.status', 'published');
        $this->postJson("/api/admin/reviews/{$review->id}/reject", ['reason' => 'Propos injurieux'])->assertOk()
            ->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.rejection_reason', 'Propos injurieux');
        $this->postJson("/api/admin/reviews/{$review->id}/publish")->assertOk()->assertJsonPath('data.rejection_reason', null);

        expect(AuditLog::query()->orderBy('id')->pluck('action')->all())->toBe([AuditAction::ReviewPublished, AuditAction::ReviewRejected, AuditAction::ReviewPublished])
            ->and(AuditLog::query()->orderBy('id')->get()[1]->after)->toMatchArray(['status' => 'rejected', 'rejection_reason' => 'Propos injurieux']);
    });

    it('répond publiquement puis efface la réponse', function () {
        $review = reviewFor($this->cocon, '2026-10-01', 'published');

        $this->putJson("/api/admin/reviews/{$review->id}/reply", ['reply' => 'Merci, à bientôt !'])->assertOk()->assertJsonPath('data.host_reply', 'Merci, à bientôt !');
        $this->putJson("/api/admin/reviews/{$review->id}/reply", ['reply' => '  '])->assertOk()->assertJsonPath('data.host_reply', null);
    });

    it('ne modère pas une invitation sans réponse, mais la renvoie avec un nouveau lien', function () {
        $review = reviewFor($this->cocon, '2026-10-01', 'invited');
        $oldHash = $review->token_hash;

        $this->postJson("/api/admin/reviews/{$review->id}/publish")->assertStatus(422)->assertJsonPath('code', 'review_not_submitted');
        $this->postJson("/api/admin/reviews/{$review->id}/resend-invitation")->assertOk()
            ->assertJsonPath('data.expires_at', fn (string $date) => str_starts_with($date, '2026-12-20'));

        expect($review->refresh()->token_hash)->not->toBe($oldHash);
        Mail::assertQueued(ReviewInvitationGuest::class);

        $submitted = reviewFor($this->cocon, '2026-09-01');
        $this->postJson("/api/admin/reviews/{$submitted->id}/resend-invitation")->assertStatus(422)->assertJsonPath('code', 'review_already_submitted');
    });

    it('compte les avis à modérer dans la barre', function () {
        reviewFor($this->cocon, '2026-10-01');
        reviewFor($this->cocon, '2026-09-01', 'published');

        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('data.pending_reviews', 1);
    });

    it('réserve la modération à l’administrateur', function () {
        $review = reviewFor($this->cocon, '2026-10-01');
        $cleaner = User::factory()->housekeeping()->create();

        $this->actingAs($cleaner)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($cleaner)->postJson("/api/admin/reviews/{$review->id}/publish")->assertForbidden();
    });
});

describe('avis publiés sur le site', function () {
    it('donne les seuls avis publiés, sans nom ni dates exactes, avec la moyenne et la répartition', function () {
        reviewFor($this->cocon, '2026-10-01', 'published', 5)->update(['host_reply' => 'Merci !']);
        reviewFor($this->nid, '2026-09-10', 'published', 4);
        reviewFor($this->nid, '2026-09-20', 'published', 4);
        reviewFor($this->cocon, '2026-08-01');
        reviewFor($this->cocon, '2026-07-01', 'published', 1)->update(['status' => ReviewStatus::Rejected]);

        $response = $this->getJson('/api/reviews')->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('summary.count', 3)
            ->assertJsonPath('summary.average', 4.3)
            ->assertJsonPath('summary.distribution', ['5' => 1, '4' => 2, '3' => 0, '2' => 0, '1' => 0]);

        expect(collect($response->json('data'))->pluck('stay_month')->all())->toContain('2026-10')
            ->and($response->getContent())->not->toContain('guest_name')->not->toContain('2026-10-01');

        $this->getJson('/api/reviews?apartment=le-nid', ['Accept-Language' => 'en'])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('summary.average', 4)
            ->assertJsonPath('data.0.apartment', ['name' => 'The Nest', 'slug' => 'le-nid']);
    });

    it('met à jour la moyenne dès qu’un avis est publié', function () {
        $review = reviewFor($this->cocon, '2026-10-01', 'submitted', 3);
        $this->getJson('/api/reviews?apartment=le-cocon')->assertJsonPath('summary.count', 0)->assertJsonPath('summary.average', null);

        $this->postJson("/api/admin/reviews/{$review->id}/publish")->assertOk();

        $this->getJson('/api/reviews?apartment=le-cocon')->assertJsonPath('summary.count', 1)->assertJsonPath('summary.average', 3);
    });

    it('masque les avis d’un logement retiré du site et refuse un logement inconnu', function () {
        reviewFor($this->nid, '2026-09-10', 'published');
        $this->nid->update(['status' => ApartmentStatus::Draft]);

        $this->getJson('/api/reviews')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/reviews?apartment=le-nid')->assertNotFound();
    });
});
