<?php

use App\Enums\AuditAction;
use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use App\Services\Availability\ManualBlockService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create();
    $this->url = "/api/admin/apartments/{$this->apartment->id}/blocks";
});

it('ferme un logement sur des nuits libres, journalisé', function () {
    $this->postJson($this->url, ['starts_on' => '2026-12-01', 'ends_on' => '2026-12-05', 'note' => ' Travaux '])
        ->assertCreated()
        ->assertJsonPath('data.type', 'manual')
        ->assertJsonPath('data.note', 'Travaux');

    expect(AvailabilityBlock::query()->where('type', BlockType::Manual)->count())->toBe(1)
        ->and(AuditLog::sole()->action)->toBe(AuditAction::BlockCreated);
    $this->getJson("/api/apartments/{$this->apartment->slug}/availability?from=2026-11-30&to=2026-12-06")->assertOk();
});

it('refuse une fermeture sur des nuits déjà occupées', function () {
    directBooking($this->apartment, '2026-12-03', '2026-12-06');

    $this->postJson($this->url, ['starts_on' => '2026-12-01', 'ends_on' => '2026-12-04'])
        ->assertStatus(409)->assertJsonPath('code', 'dates_unavailable');
    $this->postJson($this->url, ['starts_on' => '2026-12-06', 'ends_on' => '2026-12-08'])->assertCreated();
});

it('refuse des dates passées, inversées ou une fermeture de plus d’un an', function () {
    $this->postJson($this->url, ['starts_on' => '2026-11-19', 'ends_on' => '2026-11-22'])->assertJsonValidationErrors('starts_on');
    $this->postJson($this->url, ['starts_on' => '2026-12-05', 'ends_on' => '2026-12-05'])->assertJsonValidationErrors('ends_on');
    $this->postJson($this->url, ['starts_on' => '2026-12-01', 'ends_on' => '2028-01-01'])->assertJsonValidationErrors('ends_on');
});

it('retire une fermeture, libère ses nuits et lève le conflit qu’elle portait', function () {
    $block = AvailabilityBlock::factory()->for($this->apartment)->between('2026-12-01', '2026-12-05')->create(['type' => BlockType::Manual]);
    $airbnb = AvailabilityBlock::factory()->for($this->apartment)->between('2026-12-03', '2026-12-06')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->apartment)->create()->id, 'external_uid' => 'HM1', 'has_conflict' => true,
    ]);

    $this->deleteJson("/api/admin/blocks/{$block->id}")->assertNoContent();

    expect(AvailabilityBlock::find($block->id))->toBeNull()
        ->and($airbnb->fresh()->has_conflict)->toBeFalse()
        ->and(AuditLog::sole()->action)->toBe(AuditAction::BlockDeleted);
});

it('ne retire une fermeture qu’une fois, même demandée deux fois', function () {
    $block = AvailabilityBlock::factory()->for($this->apartment)->between('2026-12-01', '2026-12-05')->create(['type' => BlockType::Manual]);
    $stale = AvailabilityBlock::find($block->id);
    $service = app(ManualBlockService::class);

    $service->delete($block);

    expect(fn () => $service->delete($stale))->toThrow(ModelNotFoundException::class)
        ->and(AuditLog::query()->where('action', 'block.deleted')->count())->toBe(1);
});

it('ne retire ni une réservation ni un séjour importé', function () {
    $booking = directBooking($this->apartment, '2026-12-03', '2026-12-06');

    $this->deleteJson('/api/admin/blocks/'.$booking->availabilityBlock->id)
        ->assertUnprocessable()->assertJsonPath('code', 'block_not_removable');
});

it('réserve les fermetures à l’administrateur', function () {
    $block = AvailabilityBlock::factory()->for($this->apartment)->create(['type' => BlockType::Manual]);
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->postJson($this->url, ['starts_on' => '2026-12-01', 'ends_on' => '2026-12-05'])->assertForbidden();
    $this->deleteJson("/api/admin/blocks/{$block->id}")->assertForbidden();
});
