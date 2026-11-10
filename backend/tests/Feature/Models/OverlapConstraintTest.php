<?php

use App\Enums\BlockType;
use App\Exceptions\DatesUnavailableException;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\RatePeriod;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->apartment = Apartment::factory()->create();
    AvailabilityBlock::factory()->for($this->apartment)->between('2027-05-10', '2027-05-14')->create();
});

function block(Apartment $apartment, string $from, string $to, array $attributes = []): AvailabilityBlock
{
    return AvailabilityBlock::factory()->for($apartment)->between($from, $to)->create($attributes);
}

it('refuse deux blocs actifs qui se recouvrent', function (string $from, string $to) {
    expect(fn () => block($this->apartment, $from, $to))
        ->toThrow(fn (QueryException $e) => expect($e->getCode())->toBe('23P01'));
})->with([
    'mêmes nuits' => ['2027-05-10', '2027-05-14'],
    'chevauche le début' => ['2027-05-08', '2027-05-11'],
    'chevauche la fin' => ['2027-05-13', '2027-05-16'],
    'inclus' => ['2027-05-11', '2027-05-12'],
    'englobe' => ['2027-05-01', '2027-05-31'],
]);

it('accepte une arrivée le jour du départ précédent', function () {
    expect(block($this->apartment, '2027-05-14', '2027-05-16')->exists)->toBeTrue()
        ->and(block($this->apartment, '2027-05-07', '2027-05-10')->exists)->toBeTrue();
});

it('accepte les mêmes nuits dans un autre logement', function () {
    expect(block(Apartment::factory()->create(), '2027-05-10', '2027-05-14')->exists)->toBeTrue();
});

it('ignore les blocs désactivés et les conflits iCal signalés', function () {
    $inactive = block($this->apartment, '2027-05-10', '2027-05-14', ['is_active' => false]);
    $conflict = block($this->apartment, '2027-05-12', '2027-05-15', [
        'type' => BlockType::Ical,
        'has_conflict' => true,
    ]);

    expect($inactive->exists)->toBeTrue()->and($conflict->exists)->toBeTrue();
});

it('refuse de réactiver un bloc qui recouvre des nuits prises', function () {
    $released = block($this->apartment, '2027-05-12', '2027-05-13', ['is_active' => false]);

    expect(fn () => $released->update(['is_active' => true]))->toThrow(QueryException::class);
});

it('refuse une plage vide ou inversée', function () {
    expect(fn () => block($this->apartment, '2027-06-10', '2027-06-10'))->toThrow(QueryException::class);
});

it('refuse deux périodes tarifaires qui se recouvrent pour un logement', function () {
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2027-07-01', 'ends_on' => '2027-08-31']);

    // Savepoint : sous PostgreSQL, une erreur annule sinon toute la transaction du test.
    expect(fn () => DB::transaction(fn () => RatePeriod::factory()->for($this->apartment)->create([
        'starts_on' => '2027-08-31', 'ends_on' => '2027-09-30',
    ])))->toThrow(QueryException::class);

    $next = RatePeriod::factory()->for($this->apartment)->create([
        'starts_on' => '2027-09-01', 'ends_on' => '2027-09-30',
    ]);
    expect($next->exists)->toBeTrue();
});

it('répond 409 « dates indisponibles » quand la contrainte est violée dans une requête', function () {
    Route::post('/api/_test/block', function () {
        block($this->apartment, '2027-05-11', '2027-05-12');
    });

    $this->postJson('/api/_test/block')
        ->assertStatus(409)
        ->assertJson(['code' => 'dates_unavailable', 'message' => __('booking.dates_unavailable')]);
});

it('reconnaît une violation de contrainte d’exclusion', function () {
    $caught = null;
    try {
        DB::transaction(fn () => block($this->apartment, '2027-05-10', '2027-05-11'));
    } catch (QueryException $e) {
        $caught = $e;
    }

    expect($caught)->not->toBeNull()
        ->and(DatesUnavailableException::isExclusionViolation($caught))->toBeTrue();
});
