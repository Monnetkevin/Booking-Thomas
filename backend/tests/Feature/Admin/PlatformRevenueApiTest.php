<?php

use App\Enums\AuditAction;
use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\PlatformMonthlyRevenue;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-22 10:00:00');
    $this->admin = User::factory()->create(['name' => 'Hôte']);
    $this->actingAs($this->admin);
    $this->mancelle = Apartment::factory()->create(['name' => ['fr' => 'L’élégance Mancelle', 'en' => 'Mancelle'], 'position' => 1]);
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon'], 'position' => 2]);
    $this->airbnb = IcalFeed::factory()->for($this->mancelle)->create();
    $this->booking = IcalFeed::factory()->for($this->cocon)->create(['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/ical.ics?t=x']);
});

function platformStay(IcalFeed $feed, string $from, string $to, string $uid, ?string $note = null): void
{
    AvailabilityBlock::factory()->for($feed->apartment)->between($from, $to)->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => $uid, 'note' => $note,
    ]);
}

/** @return array{apartment_id: int, platform: string, net_amount: int|null} */
function revenueEntry(Apartment $apartment, string $platform, ?int $net): array
{
    return ['apartment_id' => $apartment->id, 'platform' => $platform, 'net_amount' => $net];
}

it('montre par défaut le mois précédent : nuits iCal par logement et plateforme, montants à saisir', function () {
    platformStay($this->airbnb, '2026-10-05', '2026-10-10', 'A1');
    platformStay($this->airbnb, '2026-10-29', '2026-11-03', 'A2');                    // 3 nuits en octobre
    platformStay($this->airbnb, '2026-10-20', '2026-10-22', 'A3', 'Airbnb (Not available)');
    platformStay($this->booking, '2026-10-12', '2026-10-14', 'B1', 'CLOSED - Not available');

    $this->getJson('/api/admin/platform-revenues')->assertOk()
        ->assertJsonPath('data.month', '2026-10')
        ->assertJsonPath('data.is_current', false)
        ->assertJsonPath('data.apartments.0.name', 'L’élégance Mancelle')
        ->assertJsonPath('data.apartments.0.platforms.airbnb', ['nights' => 8, 'net_amount' => null])
        ->assertJsonPath('data.apartments.0.platforms.booking', ['nights' => 0, 'net_amount' => null])
        ->assertJsonPath('data.apartments.1.platforms.booking', ['nights' => 2, 'net_amount' => null])
        ->assertJsonPath('data.missing', 2)
        ->assertJsonPath('data.totals', ['airbnb' => 0, 'booking' => 0])
        ->assertJsonPath('data.last_saved', null);
    $this->getJson('/api/admin/platform-revenues?month=2026-11')->assertJsonPath('data.is_current', true);
});

it('enregistre, modifie et efface les montants du mois, et le journalise', function () {
    platformStay($this->airbnb, '2026-10-05', '2026-10-31', 'A1');
    platformStay($this->booking, '2026-10-12', '2026-10-14', 'B1', 'CLOSED - Not available');

    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [
        revenueEntry($this->mancelle, 'airbnb', 155207),
        revenueEntry($this->cocon, 'booking', 0),
    ]])->assertOk()
        ->assertJsonPath('data.apartments.0.platforms.airbnb', ['nights' => 26, 'net_amount' => 155207])
        ->assertJsonPath('data.apartments.1.platforms.booking', ['nights' => 2, 'net_amount' => 0])
        ->assertJsonPath('data.missing', 0)
        ->assertJsonPath('data.totals', ['airbnb' => 155207, 'booking' => 0])
        ->assertJsonPath('data.last_saved.by', 'Hôte');

    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [
        revenueEntry($this->mancelle, 'airbnb', 150000),
        revenueEntry($this->cocon, 'booking', null),
    ]])->assertOk()
        ->assertJsonPath('data.apartments.0.platforms.airbnb.net_amount', 150000)
        ->assertJsonPath('data.apartments.1.platforms.booking.net_amount', null)
        ->assertJsonPath('data.missing', 1);

    expect(PlatformMonthlyRevenue::query()->count())->toBe(1)
        ->and(PlatformMonthlyRevenue::sole()->month->toDateString())->toBe('2026-10-01')
        ->and(AuditLog::query()->where('action', AuditAction::PlatformRevenuesSaved)->count())->toBe(2)
        ->and(AuditLog::query()->latest('id')->first()->after['month'])->toBe('2026-10');
});

it('refuse un mois à venir, un montant négatif ou décimal, un logement supprimé, une plateforme inconnue', function () {
    $deleted = Apartment::factory()->create();
    $deleted->delete();

    $this->getJson('/api/admin/platform-revenues?month=2026-12')->assertJsonValidationErrors('month');
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-12', 'entries' => []])->assertJsonValidationErrors('month');
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [
        revenueEntry($this->mancelle, 'airbnb', -1),
        ['apartment_id' => $this->cocon->id, 'platform' => 'booking', 'net_amount' => 12.5],
        revenueEntry($deleted, 'airbnb', 100),
        revenueEntry($this->cocon, 'abritel', 100),
    ]])->assertJsonValidationErrors(['entries.0.net_amount', 'entries.1.net_amount', 'entries.2.apartment_id', 'entries.3.platform']);

    expect(PlatformMonthlyRevenue::query()->count())->toBe(0);
});

it('réserve la saisie à l’administrateur', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->getJson('/api/admin/platform-revenues')->assertForbidden();
    $this->getJson('/api/admin/platform-revenues/history')->assertForbidden();
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => []])->assertForbidden();
})->with(['manager', 'housekeeping']);

it('résume les douze derniers mois, le plus récent d’abord', function () {
    platformStay($this->airbnb, '2026-10-05', '2026-10-10', 'A1');
    platformStay($this->booking, '2026-09-12', '2026-09-14', 'B1', 'CLOSED - Not available');
    platformStay($this->airbnb, '2026-11-02', '2026-11-04', 'A2');
    PlatformMonthlyRevenue::query()->create(['platform' => 'booking', 'apartment_id' => $this->cocon->id, 'month' => '2026-09-01', 'net_amount' => 21000]);

    $history = $this->getJson('/api/admin/platform-revenues/history')->assertOk()->assertJsonCount(12, 'data')->json('data');

    expect($history[0])->toMatchArray(['month' => '2026-11', 'is_current' => true, 'missing' => 1, 'entered' => false])
        ->and($history[1])->toMatchArray(['month' => '2026-10', 'missing' => 1, 'totals' => ['airbnb' => 0, 'booking' => 0]])
        ->and($history[2])->toMatchArray(['month' => '2026-09', 'missing' => 0, 'entered' => true, 'totals' => ['airbnb' => 0, 'booking' => 21000]])
        ->and($history[11]['month'])->toBe('2025-12');
});

it('alimente les statistiques avec les montants saisis', function () {
    platformStay($this->airbnb, '2026-10-05', '2026-10-31', 'A1');
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [revenueEntry($this->mancelle, 'airbnb', 155207)]])->assertOk();

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.4.platform_revenue', ['airbnb' => 155207, 'booking' => 0])
        ->assertJsonPath('data.months.4.platform_revenue_missing', false)
        ->assertJsonPath('data.totals.revenue_per_night', 5970);
});

it('accepte un montant sans nuit ce mois-là (frais d’annulation), compté au total mais pas au net par nuit', function () {
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [revenueEntry($this->cocon, 'booking', 8000)]])->assertOk()
        ->assertJsonPath('data.apartments.1.platforms.booking', ['nights' => 0, 'net_amount' => 8000])
        ->assertJsonPath('data.totals.booking', 8000)
        ->assertJsonPath('data.missing', 0);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.4.platform_revenue.booking', 8000)
        ->assertJsonPath('data.totals.revenue_per_night', null);
});

it('ignore les montants d’un logement supprimé dans l’historique et la dernière saisie', function () {
    PlatformMonthlyRevenue::query()->create(['platform' => 'airbnb', 'apartment_id' => $this->cocon->id, 'month' => '2026-09-01', 'net_amount' => 21000, 'user_id' => $this->admin->id]);
    $this->cocon->delete();

    $this->getJson('/api/admin/platform-revenues?month=2026-09')->assertJsonPath('data.last_saved', null);
    expect(collect($this->getJson('/api/admin/platform-revenues/history')->json('data'))->firstWhere('month', '2026-09'))
        ->toMatchArray(['entered' => false, 'totals' => ['airbnb' => 0, 'booking' => 0]]);
});

it('garde lisibles au journal les imports de l’ancien import CSV', function () {
    AuditLog::query()->create(['action' => 'platform_payouts.imported', 'after' => ['platform' => 'airbnb']]);

    $this->getJson('/api/admin/audit-logs')->assertOk()->assertJsonPath('data.0.action', 'platform_payouts.imported');
});

it('n’écrit et ne journalise que les montants qui changent, sans toucher aux autres cases', function () {
    $other = User::factory()->create(['name' => 'Autre admin']);
    PlatformMonthlyRevenue::query()->create(['platform' => 'booking', 'apartment_id' => $this->cocon->id, 'month' => '2026-10-01', 'net_amount' => 46800, 'user_id' => $other->id]);
    PlatformMonthlyRevenue::query()->create(['platform' => 'airbnb', 'apartment_id' => $this->mancelle->id, 'month' => '2026-10-01', 'net_amount' => 150000, 'user_id' => $other->id]);

    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [
        revenueEntry($this->mancelle, 'airbnb', 155207),
        revenueEntry($this->cocon, 'booking', 46800),   // inchangé
    ]])->assertOk();

    $log = AuditLog::query()->where('action', AuditAction::PlatformRevenuesSaved)->sole();
    expect(PlatformMonthlyRevenue::query()->where('platform', 'booking')->sole()->user_id)->toBe($other->id)
        ->and($log->before['amounts'])->toEqual([['apartment_id' => $this->mancelle->id, 'platform' => 'airbnb', 'net_amount' => 150000]])
        ->and($log->after['amounts'])->toEqual([['apartment_id' => $this->mancelle->id, 'platform' => 'airbnb', 'net_amount' => 155207]]);

    // Rien de changé : ni écriture ni journal.
    $this->putJson('/api/admin/platform-revenues', ['month' => '2026-10', 'entries' => [revenueEntry($this->mancelle, 'airbnb', 155207)]])->assertOk();
    expect(AuditLog::query()->where('action', AuditAction::PlatformRevenuesSaved)->count())->toBe(1);
});
