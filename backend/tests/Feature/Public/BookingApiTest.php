<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\RatePeriod;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Eloquent\BookingRepository;
use App\Services\Booking\BookingService;
use App\Services\Booking\StayRules;
use App\Services\Security\HumanVerifier;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    RateLimiter::clear('bookings');
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 100]);
    app(SettingRepositoryInterface::class)->put('cancellation_policies', [
        'moderate' => [['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50]],
    ]);

    $this->apartment = Apartment::factory()->create([
        'slug' => 'le-cocon', 'capacity' => 4, 'min_stay' => 2, 'max_stay' => 14, 'cleaning_fee' => 4000, 'turnover_days' => 0,
    ]);
    RatePeriod::factory()->for($this->apartment)->create([
        'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'nightly_price' => 8000, 'weekend_supplement' => null,
    ]);
});

function bookingPayload(array $overrides = []): array
{
    return array_merge([
        'apartment' => 'le-cocon', 'arrival' => '2026-11-17', 'departure' => '2026-11-20', 'adults' => 2, 'children' => 1,
        'name' => 'Camille Martin', 'email' => 'camille@example.com', 'phone' => '+33 6 12 34 56 78',
        'country' => 'fr', 'message' => 'Arrivée vers 18 h.', 'accept_terms' => true,
    ], $overrides);
}

it('crée une réservation en attente, au prix recalculé par le serveur', function () {
    $response = $this->postJson('/api/bookings', bookingPayload(['total' => 1]), ['Accept-Language' => 'en'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total', 24000 + 4000 + 600)
        ->assertJsonPath('data.expires_at', now()->addMinutes(40)->toIso8601String())
        ->assertJsonPath('data.tracking_url', fn (string $url) => str_contains($url, '/en/reservation/BT-') && str_contains($url, 'signature='));

    $booking = Booking::firstOrFail();
    expect($response->json('data.reference'))->toBe($booking->reference)->toMatch('/^BT-[A-Z0-9]{8}$/')
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->guest_country)->toBe('FR')
        ->and($booking->locale)->toBe('en')
        ->and($booking->accommodation_amount)->toBe(24000)
        ->and($booking->tourist_tax)->toBe(600)
        ->and($booking->price_breakdown['nights'])->toHaveCount(3)
        ->and($booking->cancellation_policy)->toBe([
            'policy' => 'moderate',
            'tiers' => [['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50]],
        ]);

    $block = AvailabilityBlock::firstOrFail();
    expect($block->type)->toBe(BlockType::Booking)
        ->and($block->booking_id)->toBe($booking->id)
        ->and($block->is_active)->toBeTrue()
        ->and($block->starts_on->toDateString())->toBe('2026-11-17')
        ->and($block->ends_on->toDateString())->toBe('2026-11-20');
});

it('bloque les nuits : un second voyageur ne peut plus les réserver', function () {
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();

    $this->postJson('/api/bookings', bookingPayload(['email' => 'autre@example.com', 'arrival' => '2026-11-18']))
        ->assertUnprocessable();
    $this->getJson('/api/apartments/le-cocon/availability?from=2026-11-01&to=2026-11-30')
        ->assertJsonFragment(['2026-11-17']);
});

it('répond 409 si les nuits sont prises au même instant par un autre voyageur', function () {
    // Les règles passent (vérifiées avant l'autre réservation), la contrainte PostgreSQL tranche.
    $this->mock(StayRules::class, function ($rules) {
        $rules->shouldReceive('violations')->andReturn([]);
        $rules->shouldReceive('isFree')->andReturn(true);
    });
    AvailabilityBlock::factory()->for($this->apartment)->create([
        'starts_on' => '2026-11-18', 'ends_on' => '2026-11-19', 'type' => BlockType::Manual,
    ]);

    $this->postJson('/api/bookings', bookingPayload())
        ->assertStatus(409)
        ->assertJsonPath('code', 'dates_unavailable');
    expect(Booking::count())->toBe(0);
});

it('répond 409 si le battement de ménage est pris au même instant (revérifié sous le verrou du logement)', function () {
    // Le devis passe (calculé avant l'autre réservation) ; le bloc voisin ne recouvre pas
    // les nuits, donc la contrainte PostgreSQL ne dit rien : c'est le verrou qui tranche.
    $this->apartment->update(['turnover_days' => 1]);
    $rules = Mockery::mock(StayRules::class, [
        app(RatePeriodRepositoryInterface::class),
        app(AvailabilityRepositoryInterface::class),
        app(SettingRepositoryInterface::class),
    ])->makePartial();
    $rules->shouldReceive('violations')->andReturn([]);
    $this->instance(StayRules::class, $rules);
    AvailabilityBlock::factory()->for($this->apartment)->create([
        'starts_on' => '2026-11-20', 'ends_on' => '2026-11-22', 'type' => BlockType::Manual,
    ]);

    $this->postJson('/api/bookings', bookingPayload())
        ->assertStatus(409)
        ->assertJsonPath('code', 'dates_unavailable');
    expect(Booking::count())->toBe(0);
});

it('refuse un séjour hors des règles du logement', function () {
    $this->postJson('/api/bookings', bookingPayload(['adults' => 4, 'children' => 2]))->assertUnprocessable();
    expect(Booking::count())->toBe(0);
});

it('exige les coordonnées et l’acceptation des CGV', function () {
    $this->postJson('/api/bookings', bookingPayload(['accept_terms' => false, 'country' => 'France', 'phone' => '']))
        ->assertJsonValidationErrors(['accept_terms', 'country', 'phone']);
});

it('refuse une réservation si le défi anti-robot échoue', function () {
    $this->app->instance(HumanVerifier::class, new class implements HumanVerifier
    {
        public function verify(?string $token, ?string $ip): bool
        {
            return false;
        }
    });

    $this->postJson('/api/bookings', bookingPayload())->assertJsonValidationErrors('captcha');
    expect(Booking::count())->toBe(0);
});

it('répond 404 pour un logement non publié', function () {
    Apartment::factory()->draft()->create(['slug' => 'brouillon']);

    $this->postJson('/api/bookings', bookingPayload(['apartment' => 'brouillon']))->assertNotFound();
});

it('limite à 5 réservations par minute et par adresse IP', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/bookings', bookingPayload(['arrival' => "2026-11-0$i", 'departure' => '2026-11-0'.($i + 2)]));
    }

    $this->postJson('/api/bookings', bookingPayload())->assertTooManyRequests();
});

it('expire une réservation non payée après 40 minutes et libère ses nuits', function () {
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();

    Date::setTestNow('2026-11-10 14:39:00');
    $this->artisan('bookings:expire')->assertSuccessful();
    expect(Booking::firstOrFail()->status)->toBe(BookingStatus::Pending);

    Date::setTestNow('2026-11-10 14:41:00');
    $this->artisan('bookings:expire')->expectsOutputToContain('1 réservation(s) expirée(s)')->assertSuccessful();

    expect(Booking::firstOrFail()->status)->toBe(BookingStatus::Expired)
        ->and(AvailabilityBlock::firstOrFail()->is_active)->toBeFalse();
    $this->postJson('/api/bookings', bookingPayload(['email' => 'autre@example.com']))->assertCreated();
});

it('n’expire pas une réservation confirmée entre-temps par le paiement', function () {
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();
    $booking = Booking::firstOrFail();
    Date::setTestNow('2026-11-10 14:41:00');

    // La tâche d'expiration a déjà lu la réservation « pending » quand le paiement la confirme.
    $bookings = Mockery::mock(BookingRepository::class)->makePartial();
    $bookings->shouldReceive('overduePending')->andReturnUsing(function () use ($booking) {
        $stale = Booking::query()->whereKey($booking->id)->get();
        app(BookingService::class)->confirm($booking);

        return $stale;
    });
    $this->instance(BookingRepositoryInterface::class, $bookings);

    $this->artisan('bookings:expire')->expectsOutputToContain('0 réservation(s) expirée(s)')->assertSuccessful();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and(AvailabilityBlock::firstOrFail()->is_active)->toBeTrue();
});

it('ne confirme pas une réservation expirée entre-temps', function () {
    Event::fake([BookingConfirmed::class]);
    $this->postJson('/api/bookings', bookingPayload())->assertCreated();
    $stale = Booking::firstOrFail();
    Date::setTestNow('2026-11-10 14:41:00');
    $this->artisan('bookings:expire')->assertSuccessful();

    // Le modèle en main date d'avant l'expiration : il se croit encore « pending ».
    app(BookingService::class)->confirm($stale);

    expect($stale->fresh()->status)->toBe(BookingStatus::Expired)
        ->and($stale->status)->toBe(BookingStatus::Expired)
        ->and(AvailabilityBlock::firstOrFail()->is_active)->toBeFalse();
    Event::assertNotDispatched(BookingConfirmed::class);
});

it('planifie l’expiration chaque minute', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('bookings:expire');
});
