<?php

use App\Enums\AuditAction;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Extra;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->loft = Apartment::factory()->create(['name' => ['fr' => 'Le Loft', 'en' => 'The Loft']]);
    $this->champagne = Extra::factory()->create(['name' => ['fr' => 'Champagne']]);
});

function couponPayload(array $overrides = []): array
{
    return [...[
        'code' => 'bienvenue-10', 'target' => 'stay', 'type' => 'percent', 'value' => 10, 'extra_id' => null, 'free_quantity' => null,
        'starts_on' => '2026-12-01', 'ends_on' => '2027-03-31', 'max_uses' => 50, 'max_uses_per_email' => 1, 'min_nights' => 2,
        'min_amount' => null, 'automatic' => false, 'is_active' => true, 'apartment_ids' => [],
    ], ...$overrides];
}

it('crée un code en majuscules puis le transforme en offre de champagne, en traçant chaque changement', function () {
    $id = $this->postJson('/api/admin/coupons', couponPayload())->assertCreated()
        ->assertJsonPath('data.code', 'BIENVENUE-10')
        ->assertJsonPath('data.uses', 0)
        ->json('data.id');

    $this->putJson("/api/admin/coupons/$id", couponPayload([
        'code' => 'LOFT4', 'target' => 'stay', 'type' => 'free_extra', 'value' => null, 'extra_id' => $this->champagne->id,
        'free_quantity' => 1, 'min_nights' => 4, 'automatic' => true, 'apartment_ids' => [$this->loft->id],
    ]))->assertOk()
        ->assertJsonPath('data.target', 'extra')
        ->assertJsonPath('data.value', 0)
        ->assertJsonPath('data.extra', ['id' => $this->champagne->id, 'name' => 'Champagne'])
        ->assertJsonPath('data.apartments', [['id' => $this->loft->id, 'name' => 'Le Loft']]);

    expect(AuditLog::query()->orderBy('id')->pluck('action')->all())->toBe([AuditAction::CouponCreated, AuditAction::CouponUpdated]);
});

it('valide le code, la valeur selon le type, l’option visée et les dates', function () {
    Coupon::factory()->create(['code' => 'PRIS']);

    $this->postJson('/api/admin/coupons', couponPayload(['code' => 'pris', 'value' => 150, 'ends_on' => '2026-11-01']))
        ->assertJsonValidationErrors(['code', 'value', 'ends_on']);
    $this->postJson('/api/admin/coupons', couponPayload(['code' => 'a b', 'target' => 'extra']))
        ->assertJsonValidationErrors(['code', 'extra_id']);
});

it('liste les codes avec leurs utilisations et le montant remisé, sans les réservations expirées', function () {
    $coupon = Coupon::factory()->create(['code' => 'BIENVENUE']);
    foreach ([[2000, null], [2000, now()], [1500, null]] as [$discount, $released]) {
        CouponRedemption::query()->create(['coupon_id' => $coupon->id, 'booking_id' => directBooking($this->loft, Date::now()->addDays(10 + 3 * CouponRedemption::query()->count())->toDateString(), Date::now()->addDays(12 + 3 * CouponRedemption::query()->count())->toDateString())->id,
            'email' => 'v@example.com', 'code' => 'BIENVENUE', 'discount_amount' => $discount, 'released_at' => $released]);
    }

    $this->getJson('/api/admin/coupons')->assertOk()->assertJsonPath('data.0.uses', 2)->assertJsonPath('data.0.discount_total', 3500);
});

it('réserve les codes à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/coupons')->assertForbidden();
});
