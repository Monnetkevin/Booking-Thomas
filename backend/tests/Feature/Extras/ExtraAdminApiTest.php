<?php

use App\Enums\AuditAction;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Extra;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->loft = Apartment::factory()->create(['name' => ['fr' => 'Le Loft', 'en' => 'The Loft'], 'position' => 1]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'The Nest'], 'position' => 2]);
});

function extraPayload(array $overrides = []): array
{
    return [...[
        'name_fr' => 'Bouteille de champagne', 'name_en' => 'Bottle of champagne', 'description_fr' => 'Servie fraîche.', 'description_en' => '',
        'price' => 4500, 'pricing' => 'per_unit', 'max_quantity' => 3, 'lead_time_hours' => 48, 'is_active' => true, 'apartment_ids' => [],
    ], ...$overrides];
}

it('crée puis modifie une option et ses logements, en traçant chaque changement', function () {
    $id = $this->postJson('/api/admin/extras', extraPayload(['apartment_ids' => [$this->loft->id]]))->assertCreated()
        ->assertJsonPath('data.name_en', 'Bottle of champagne')
        ->assertJsonPath('data.description_en', null)
        ->assertJsonPath('data.apartments', [['id' => $this->loft->id, 'name' => 'Le Loft']])
        ->json('data.id');

    // Une option par séjour ne se commande qu'une fois.
    $this->putJson("/api/admin/extras/$id", extraPayload(['pricing' => 'per_stay', 'price' => 0, 'is_active' => false, 'apartment_ids' => [$this->loft->id, $this->nid->id]]))->assertOk()
        ->assertJsonPath('data.max_quantity', 1)
        ->assertJsonPath('data.price', 0)
        ->assertJsonPath('data.is_active', false)
        ->assertJsonCount(2, 'data.apartments');

    expect(AuditLog::query()->orderBy('id')->pluck('action')->all())->toBe([AuditAction::ExtraCreated, AuditAction::ExtraUpdated])
        ->and(AuditLog::query()->orderByDesc('id')->first()->before)->toMatchArray(['price' => 4500, 'is_active' => true]);
});

it('valide le nom, le prix, le mode et les logements', function () {
    $this->postJson('/api/admin/extras', extraPayload(['name_fr' => ' ', 'price' => -1, 'pricing' => 'par_jour', 'apartment_ids' => [999]]))
        ->assertJsonValidationErrors(['name_fr', 'price', 'pricing', 'apartment_ids.0']);
});

it('liste le catalogue avec les chiffres du mois et les options à préparer', function () {
    $champagne = Extra::factory()->create(['name' => ['fr' => 'Champagne']]);
    Extra::factory()->create(['is_active' => false]);
    $champagne->apartments()->attach($this->loft);
    $soon = directBooking($this->loft, '2026-11-22', '2026-11-24');
    $soon->extras()->create(['extra_id' => $champagne->id, 'name' => 'Champagne', 'pricing' => 'per_unit', 'quantity' => 2, 'unit_amount' => 4500, 'amount' => 9000]);
    // Une bouteille sur deux offerte par un code : elle n'est pas comptée dans le chiffre d'affaires.
    $soon->update(['extras_amount' => 9000, 'discount_amount' => 4500, 'extras_discount_amount' => 4500]);
    $later = directBooking($this->loft, '2026-12-20', '2026-12-22');
    $later->extras()->create(['extra_id' => $champagne->id, 'name' => 'Champagne', 'pricing' => 'per_unit', 'quantity' => 1, 'unit_amount' => 4500, 'amount' => 4500]);
    directBooking($this->nid, '2026-11-25', '2026-11-27');
    directBooking($this->nid, '2026-11-28', '2026-11-29', BookingStatus::Pending)
        ->extras()->create(['extra_id' => $champagne->id, 'name' => 'Champagne', 'pricing' => 'per_unit', 'quantity' => 1, 'unit_amount' => 4500, 'amount' => 4500]);

    $this->getJson('/api/admin/extras')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.figures', ['active' => 1, 'total' => 2, 'bookings' => 2, 'with_extras' => 1, 'revenue' => 4500, 'previous_revenue' => 0]);

    $this->getJson('/api/admin/extras/to-prepare')->assertOk()
        ->assertJsonPath('data', [[
            'reference' => $soon->reference, 'arrival' => '2026-11-22', 'apartment' => 'Le Loft',
            'items' => [['name' => 'Champagne', 'quantity' => 2]],
        ]]);
});

it('réserve les options à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/extras')->assertForbidden();
});
