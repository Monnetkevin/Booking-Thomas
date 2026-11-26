<?php

use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-10');
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create();
    $this->url = "/api/admin/apartments/{$this->apartment->id}/rate-periods";
});

function period(array $overrides = []): array
{
    return array_merge([
        'label' => 'Hiver',
        'starts_on' => '2026-12-01',
        'ends_on' => '2027-01-04',
        'nightly_price' => 9500,
        'min_stay' => 3,
        'weekend_supplement' => 1500,
        'arrival_days' => null,
    ], $overrides);
}

it('crée une période tarifaire en centimes', function () {
    $this->postJson($this->url, period())
        ->assertCreated()
        ->assertJsonPath('data.label', 'Hiver')
        ->assertJsonPath('data.starts_on', '2026-12-01')
        ->assertJsonPath('data.nightly_price', 9500)
        ->assertJsonPath('data.weekend_supplement', 1500);
});

it('liste les périodes à venir dans l’ordre, les passées sur demande', function () {
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2027-03-01', 'ends_on' => '2027-03-31']);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-30']);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-01-31']);

    $this->getJson($this->url)->assertJsonPath('data.*.starts_on', ['2026-11-01', '2027-03-01']);
    $this->getJson("{$this->url}?include_past=1")->assertJsonCount(3, 'data');
});

it('refuse une période qui en chevauche une autre, en la nommant', function () {
    RatePeriod::factory()->for($this->apartment)->create([
        'label' => 'Noël', 'starts_on' => '2026-12-20', 'ends_on' => '2027-01-02',
    ]);

    $this->postJson($this->url, period(['starts_on' => '2027-01-02', 'ends_on' => '2027-01-31']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_on' => 'Cette période chevauche la période « Noël » (du 20 décembre 2026 au 2 janvier 2027).']);
});

it('accepte une période qui commence le lendemain de la précédente', function () {
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-12-01', 'ends_on' => '2026-12-31']);

    $this->postJson($this->url, period(['starts_on' => '2027-01-01', 'ends_on' => '2027-01-31']))->assertCreated();
});

it('accepte les mêmes dates pour un autre logement', function () {
    RatePeriod::factory()->for(Apartment::factory())->create(['starts_on' => '2026-12-01', 'ends_on' => '2027-01-04']);

    $this->postJson($this->url, period())->assertCreated();
});

it('modifie une période sans se heurter à elle-même', function () {
    $existing = RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-12-01', 'ends_on' => '2026-12-31']);

    $this->putJson("/api/admin/rate-periods/{$existing->id}", period(['starts_on' => '2026-12-05', 'nightly_price' => 11000]))
        ->assertOk()
        ->assertJsonPath('data.starts_on', '2026-12-05')
        ->assertJsonPath('data.nightly_price', 11000);
});

it('valide les dates, le prix et les jours d’arrivée', function () {
    $this->postJson($this->url, period([
        'starts_on' => '2027-02-10',
        'ends_on' => '2027-02-01',
        'nightly_price' => 0,
        'arrival_days' => [8],
    ]))->assertJsonValidationErrors(['ends_on', 'nightly_price', 'arrival_days.0']);
});

it('considère « tous les jours d’arrivée » comme aucune restriction', function () {
    $this->postJson($this->url, period(['arrival_days' => [7, 1, 2, 3, 4, 5, 6]]))->assertJsonPath('data.arrival_days', null);
    $this->postJson($this->url, period(['starts_on' => '2027-02-01', 'ends_on' => '2027-02-28', 'arrival_days' => [6, 5]]))
        ->assertJsonPath('data.arrival_days', [5, 6]);
});

it('supprime une période', function () {
    $existing = RatePeriod::factory()->for($this->apartment)->create();

    $this->deleteJson("/api/admin/rate-periods/{$existing->id}")->assertNoContent();
    $this->assertModelMissing($existing);
});

it('ferme les tarifs au rôle ménage', function () {
    $existing = RatePeriod::factory()->for($this->apartment)->create();
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson($this->url)->assertForbidden();
    $this->deleteJson("/api/admin/rate-periods/{$existing->id}")->assertForbidden();
});
