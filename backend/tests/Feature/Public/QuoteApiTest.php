<?php

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\RatePeriod;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Catalog\CatalogCache;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-10'); // mardi
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 100]);

    $this->apartment = Apartment::factory()->create([
        'slug' => 'le-cocon', 'capacity' => 4, 'min_stay' => 2, 'max_stay' => 14, 'cleaning_fee' => 4000, 'turnover_days' => 0,
    ]);
    RatePeriod::factory()->for($this->apartment)->create([
        'label' => 'Automne', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'nightly_price' => 8000,
    ]);
    RatePeriod::factory()->for($this->apartment)->create([
        'label' => 'Fêtes', 'starts_on' => '2026-12-01', 'ends_on' => '2027-01-04', 'nightly_price' => 9500,
        'min_stay' => 3, 'arrival_days' => [5, 6],
    ]);
});

function quote(array $overrides = [])
{
    return test()->postJson('/api/quotes', array_merge([
        'apartment' => 'le-cocon', 'arrival' => '2026-11-20', 'departure' => '2026-11-23', 'adults' => 2, 'children' => 0,
    ], $overrides));
}

function violationCodes($response): array
{
    return array_column($response->json('details.violations') ?? [], 'code');
}

it('renvoie le détail du prix calculé par le serveur', function () {
    quote()->assertOk()
        ->assertJsonPath('data.nights', 3)
        ->assertJsonPath('data.accommodation', 24000)
        ->assertJsonPath('data.cleaning_fee', 4000)
        ->assertJsonPath('data.tourist_tax', 600)
        ->assertJsonPath('data.total', 28600)
        ->assertJsonPath('data.lines.0.label', "3 nuits × 80\u{00A0}€");
});

it('traduit les libellés en anglais', function () {
    $this->postJson('/api/quotes', [
        'apartment' => 'le-cocon', 'arrival' => '2026-11-20', 'departure' => '2026-11-23', 'adults' => 2,
    ], ['Accept-Language' => 'en'])->assertJsonPath('data.lines.1.label', 'Cleaning fee');
});

it('applique les règles du séjour', function (array $stay, string $code) {
    expect(violationCodes(quote($stay)->assertUnprocessable()->assertJsonPath('code', 'stay_not_allowed')))->toContain($code);
})->with([
    'trop de voyageurs' => [['adults' => 3, 'children' => 2], 'too_many_guests'],
    'arrivée aujourd’hui (préavis 1 jour)' => [['arrival' => '2026-11-10', 'departure' => '2026-11-13'], 'too_soon'],
    'au-delà de l’horizon de 12 mois' => [['arrival' => '2027-11-12', 'departure' => '2027-11-15'], 'too_far'],
    'séjour minimum du logement' => [['departure' => '2026-11-21'], 'min_stay'],
    'séjour minimum de la période d’arrivée' => [['arrival' => '2026-12-04', 'departure' => '2026-12-06'], 'min_stay'],
    'séjour maximum' => [['arrival' => '2026-11-12', 'departure' => '2026-11-27'], 'max_stay'],
    'jour d’arrivée' => [['arrival' => '2026-12-07', 'departure' => '2026-12-10'], 'arrival_day'],
    'nuits sans tarif' => [['arrival' => '2027-01-01', 'departure' => '2027-01-08'], 'no_rate'],
]);

it('explique la règle dans la langue de la page', function () {
    quote(['arrival' => '2026-12-04', 'departure' => '2026-12-06'])
        ->assertJsonPath('message', 'Pour cette arrivée, le séjour minimum est de 3 nuits.');
});

it('refuse des nuits déjà prises', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-21', '2026-11-24')->create();

    expect(violationCodes(quote()))->toBe(['unavailable']);
});

it('accepte une arrivée le jour d’un départ, sauf battement de ménage', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-17', '2026-11-20')->create();
    quote()->assertOk();

    $this->apartment->update(['turnover_days' => 1]);
    app(CatalogCache::class)->flush(); // fait par l'admin à chaque modification
    expect(violationCodes(quote()))->toBe(['unavailable']);
});

it('ignore les blocs désactivés et les conflits iCal signalés', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['is_active' => false]);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create([
        'type' => BlockType::Ical, 'has_conflict' => true,
    ]);

    quote()->assertOk();
});

it('répond 404 pour un logement non publié', function () {
    $this->apartment->update(['status' => 'draft']);

    quote()->assertNotFound();
});

it('valide les dates et les voyageurs', function () {
    quote(['departure' => '2026-11-20', 'adults' => 0])->assertJsonValidationErrors(['departure', 'adults']);
});

it('limite les devis à 30 par minute et par IP', function () {
    foreach (range(1, 30) as $ignored) {
        quote();
    }

    quote()->assertTooManyRequests();
});

describe('aperçu de l’hôte', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->create()));

    it('donne le prix d’un logement en brouillon, aux dates passées ou prises', function () {
        $this->apartment->update(['status' => 'draft']);
        AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create();

        $this->postJson("/api/admin/apartments/{$this->apartment->id}/quote-preview", [
            'arrival' => '2026-11-20', 'departure' => '2026-11-23', 'adults' => 2,
        ])->assertOk()->assertJsonPath('data.quote.total', 28600)->assertJsonPath('data.warnings', []);
    });

    it('signale les règles non respectées à côté du prix', function () {
        $this->postJson("/api/admin/apartments/{$this->apartment->id}/quote-preview", [
            'arrival' => '2026-12-04', 'departure' => '2026-12-06', 'adults' => 2,
        ])->assertOk()
            ->assertJsonPath('data.quote.accommodation', 19000)
            ->assertJsonPath('data.warnings.0.code', 'min_stay');
    });

    it('ne donne pas de prix pour des nuits sans tarif', function () {
        $this->postJson("/api/admin/apartments/{$this->apartment->id}/quote-preview", [
            'arrival' => '2027-01-03', 'departure' => '2027-01-06', 'adults' => 2,
        ])->assertJsonPath('data.quote', null)
            ->assertJsonPath('data.warnings.*.code', fn (array $codes) => in_array('no_rate', $codes, true));
    });
});
