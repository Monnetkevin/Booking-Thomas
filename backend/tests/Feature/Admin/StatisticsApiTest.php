<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\PlatformPayout;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon'], 'position' => 1]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => '=Le Nid; "chic"', 'en' => 'Nest'], 'position' => 2]);
});

function soldBooking(Apartment $apartment, string $arrival, string $departure, int $accommodation, BookingStatus $status = BookingStatus::Confirmed): void
{
    directBooking($apartment, $arrival, $departure, $status)->update([
        'accommodation_amount' => $accommodation, 'cleaning_fee' => 4000, 'tourist_tax' => 500,
        'total_amount' => $accommodation + 4500,
    ]);
}

it('donne les chiffres des 12 derniers mois, par mois et par logement', function () {
    soldBooking($this->cocon, '2026-10-10', '2026-10-14', 40000);           // 4 nuits en octobre
    soldBooking($this->cocon, '2026-11-28', '2026-12-02', 36000);           // 3 nuits en novembre (+1 en décembre, hors période)
    soldBooking($this->nid, '2026-11-02', '2026-11-04', 99999, BookingStatus::Pending); // pas une vente
    soldBooking($this->nid, '2025-10-10', '2025-10-12', 50000);             // hors des 12 mois
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-16')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->nid)->create()->id, 'external_uid' => 'HM1',
    ]);

    $response = $this->getJson('/api/admin/statistics')->assertOk()
        ->assertJsonPath('data.from', '2025-12-01')
        ->assertJsonPath('data.to', '2026-12-01')
        ->assertJsonCount(12, 'data.months')
        ->assertJsonPath('data.months.10.month', '2026-10')
        ->assertJsonPath('data.months.10.nights_direct', 4)
        ->assertJsonPath('data.months.10.revenue', 44000)
        ->assertJsonPath('data.months.10.occupancy', 6.5)
        ->assertJsonPath('data.months.10.platform_revenue_missing', false)
        ->assertJsonPath('data.months.11.platform_revenue_missing', true)
        ->assertJsonPath('data.months.11.nights_direct', 3)
        ->assertJsonPath('data.months.11.nights_platform', 6)
        ->assertJsonPath('data.months.11.revenue', 40000)
        ->assertJsonPath('data.totals.revenue', 84000)
        ->assertJsonPath('data.totals.nights_direct', 7)
        ->assertJsonPath('data.totals.nights_platform', 6)
        // 76 000 c d'hébergement pour 8 nuits vendues (dont une en décembre).
        ->assertJsonPath('data.totals.average_nightly_price', 9500)
        ->assertJsonPath('data.apartments.0.name', 'Le Cocon')
        ->assertJsonPath('data.apartments.0.revenue', 84000)
        ->assertJsonPath('data.apartments.1.nights_platform', 6)
        ->assertJsonPath('data.apartments.1.rating', null);

    // Séjours vendus : 4, 4 (en entier, même à cheval sur décembre) et 6 nuits.
    expect($response->json('data.totals.average_stay'))->toEqual(4.7);
});

it('ne compte pas les logements supprimés, ni au numérateur ni au dénominateur', function () {
    soldBooking($this->cocon, '2026-10-10', '2026-10-14', 40000);
    $deleted = Apartment::factory()->create();
    soldBooking($deleted, '2026-10-01', '2026-10-31', 90000);
    $deleted->delete();

    $this->getJson('/api/admin/statistics')
        ->assertJsonPath('data.months.10.nights_direct', 4)
        ->assertJsonPath('data.months.10.occupancy', 6.5)
        ->assertJsonPath('data.totals.revenue', 44000)
        ->assertJsonCount(2, 'data.apartments');
});

it('exporte un CSV lisible par un tableur, sans formule exécutable', function () {
    soldBooking($this->cocon, '2026-10-10', '2026-10-14', 40000);

    $response = $this->get('/api/admin/statistics.csv')->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="statistiques-2026-11-20.csv"');

    $csv = $response->getContent();
    expect($csv)->toStartWith("\u{FEFF}Mois;Nuits en direct")
        ->toContain('2026-10;4;0;440,00;0,00;6,5')
        ->toContain('Le Cocon;4;0;440,00;0,00;')
        ->toContain('"\'=Le Nid; ""chic""";0;0;0,00;0,00;0,0');

    // Montant négatif (séjour offert, taxe gardée) : un nombre ; nom commençant par une tabulation : neutralisé.
    $this->nid->update(['name' => ['fr' => "\tCalc", 'en' => 'Nest']]);
    soldBooking($this->nid, '2026-09-10', '2026-09-12', 0);
    $this->nid->bookings()->update(['total_amount' => 0]);
    expect($this->get('/api/admin/statistics.csv')->getContent())->toContain('2026-09;2;0;-5,00;')->toContain("'\tCalc;2;0;-5,00;");
});

it('réserve les statistiques à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson('/api/admin/statistics')->assertForbidden();
    $this->get('/api/admin/statistics.csv')->assertForbidden();
});

it('ajoute les revenus importés des plateformes, la part du direct, et change de période', function () {
    soldBooking($this->cocon, '2026-11-02', '2026-11-04', 30000);
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM2']);
    PlatformPayout::query()->create([
        'platform' => 'airbnb', 'confirmation_code' => 'HMX', 'entry_key' => 'HMX-1', 'apartment_id' => $this->nid->id,
        'arrival' => '2026-11-10', 'departure' => '2026-11-12', 'nights' => 2, 'net_amount' => 20000,
    ]);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.from', '2026-06-01')
        ->assertJsonCount(6, 'data.months')
        ->assertJsonPath('data.months.5.platform_revenue', ['airbnb' => 20000, 'booking' => 0])
        ->assertJsonPath('data.months.5.platform_revenue_missing', false)
        ->assertJsonPath('data.totals.total_revenue', 54000)
        ->assertJsonPath('data.totals.direct_share', 63)
        ->assertJsonPath('data.totals.revenue_per_night', 13500)
        ->assertJsonPath('data.apartments.1.platform_revenue', 20000);
    $this->getJson('/api/admin/statistics?period=all')->assertJsonPath('data.from', '2026-11-01');
    $this->getJson('/api/admin/statistics?period=forever')->assertJsonValidationErrors('period');
});

it('signale les revenus manquants plateforme par plateforme', function () {
    // Nuits vendues sur Airbnb, mais seuls des revenus Booking.com importés.
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM3']);
    PlatformPayout::query()->create([
        'platform' => 'booking', 'confirmation_code' => 'BK1', 'entry_key' => 'BK1-1', 'apartment_id' => $this->cocon->id,
        'arrival' => '2026-11-05', 'departure' => '2026-11-07', 'nights' => 2, 'net_amount' => 15000,
    ]);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.5.platform_revenue', ['airbnb' => 0, 'booking' => 15000])
        ->assertJsonPath('data.months.5.platform_revenue_missing', true)
        ->assertJsonPath('data.totals.revenue_per_night', null);
});

it('ne donne pas une période « Tout » inversée quand le premier séjour est à venir', function () {
    soldBooking($this->cocon, '2027-01-10', '2027-01-14', 40000);

    $this->getJson('/api/admin/statistics?period=all')
        ->assertJsonPath('data.from', '2026-11-01')
        ->assertJsonPath('data.to', '2026-12-01')
        ->assertJsonCount(1, 'data.months');
});
