<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\PaymentType;
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
    $booking = directBooking($apartment, $arrival, $departure, $status);
    $booking->update([
        'accommodation_amount' => $accommodation, 'cleaning_fee' => 4000, 'tourist_tax' => 500,
        'total_amount' => $accommodation + 4500,
    ]);
    if ($status !== BookingStatus::Pending) {
        recordPayment($booking, $accommodation + 4500);
    }
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

    // Nom commençant par une tabulation : neutralisé.
    $this->nid->update(['name' => ['fr' => "\tCalc", 'en' => 'Nest']]);
    soldBooking($this->nid, '2026-09-10', '2026-09-12', 10000);
    expect($this->get('/api/admin/statistics.csv')->getContent())->toContain('2026-09;2;0;140,00;')->toContain("'\tCalc;2;0;140,00;");
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

it('compte l’argent encaissé : rien pour une réservation non payée, la part gardée d’une annulation', function () {
    // Saisie à la main non payée : vendue (nuits) mais rien d'encaissé.
    directBooking($this->cocon, '2026-11-02', '2026-11-04')->update(['total_amount' => 30000, 'tourist_tax' => 1000]);
    // Annulée : 40 000 payés, 25 000 remboursés, 15 000 gardés (pas de taxe de séjour due).
    $cancelled = directBooking($this->nid, '2026-11-10', '2026-11-14', BookingStatus::Cancelled);
    $cancelled->update(['total_amount' => 40000, 'tourist_tax' => 1000]);
    recordPayment($cancelled, 40000);
    recordPayment($cancelled, 25000, PaymentType::Refund);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.5.revenue', 15000)
        ->assertJsonPath('data.months.5.nights_direct', 2)
        ->assertJsonPath('data.totals.revenue', 15000);
    $this->getJson('/api/admin/dashboard')->assertJsonPath('data.figures.revenue.value', 15000);
});

it('ne divise pas les revenus d’une plateforme sans calendrier relié par les nuits des autres', function () {
    soldBooking($this->cocon, '2026-11-02', '2026-11-04', 15500);   // 2 nuits, 20 000 encaissés, 19 500 hors taxe
    PlatformPayout::query()->create([
        'platform' => 'booking', 'confirmation_code' => 'BK2', 'entry_key' => 'BK2-1', 'apartment_id' => $this->nid->id,
        'arrival' => '2026-11-10', 'departure' => '2026-11-12', 'nights' => 2, 'net_amount' => 30000,
    ]);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.totals.total_revenue', 49500)
        ->assertJsonPath('data.totals.revenue_per_night', 9750);
});

it('rapproche nuits et revenus au mois d’arrivée : un séjour à cheval ne signale rien le mois suivant', function () {
    Date::setTestNow('2027-02-20 10:00:00');
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2027-01-28', '2027-02-05')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM9']);
    PlatformPayout::query()->create([
        'platform' => 'airbnb', 'confirmation_code' => 'HM9', 'entry_key' => 'HM9-1', 'apartment_id' => $this->nid->id,
        'arrival' => '2027-01-28', 'departure' => '2027-02-05', 'nights' => 8, 'net_amount' => 80000,
    ]);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.4.month', '2027-01')
        ->assertJsonPath('data.months.4.platform_revenue_missing', false)
        ->assertJsonPath('data.months.5.month', '2027-02')
        ->assertJsonPath('data.months.5.nights_platform', 4)
        ->assertJsonPath('data.months.5.platform_revenue_missing', false);
});

it('compte les dates fermées sur Airbnb comme fermées, et un bloc Booking.com comme vendu seulement si un versement le confirme', function () {
    $airbnb = IcalFeed::factory()->for($this->nid)->create();
    $booking = IcalFeed::factory()->for($this->cocon)->create(['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/ical.ics?t=x']);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $airbnb->id, 'external_uid' => 'A1', 'note' => 'Reserved']);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-20', '2026-11-30')->create(['type' => BlockType::Ical, 'ical_feed_id' => $airbnb->id, 'external_uid' => 'A2', 'note' => 'Airbnb (Not available)']);
    // Booking.com : même résumé pour une réservation et une fermeture.
    AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-03', '2026-11-06')->create(['type' => BlockType::Ical, 'ical_feed_id' => $booking->id, 'external_uid' => 'B1', 'note' => 'CLOSED - Not available']);
    AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-15', '2026-11-25')->create(['type' => BlockType::Ical, 'ical_feed_id' => $booking->id, 'external_uid' => 'B2', 'note' => 'CLOSED - Not available']);
    PlatformPayout::query()->create([
        'platform' => 'airbnb', 'confirmation_code' => 'HMA', 'entry_key' => 'HMA-1', 'apartment_id' => $this->nid->id,
        'arrival' => '2026-11-10', 'departure' => '2026-11-12', 'nights' => 2, 'net_amount' => 20000,
    ]);
    PlatformPayout::query()->create([
        'platform' => 'booking', 'confirmation_code' => 'BKA', 'entry_key' => 'BKA-1', 'apartment_id' => $this->cocon->id,
        'arrival' => '2026-11-03', 'departure' => '2026-11-06', 'nights' => 3, 'net_amount' => 36000,
    ]);

    // Novembre : 2 logements × 30 nuits, 20 fermées (10 Airbnb, 10 Booking.com), 5 vendues.
    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.5.nights_platform', 5)
        ->assertJsonPath('data.months.5.occupancy', 12.5)
        ->assertJsonPath('data.months.5.platform_revenue_missing', false)
        ->assertJsonPath('data.totals.revenue_per_night', 11200)
        ->assertJsonPath('data.totals.average_stay', 2.5);
    // Le tableau de bord ne montre pas une date fermée sur Airbnb comme une arrivée.
    $arrivals = collect($this->getJson('/api/admin/dashboard')->json('data.movements'))->firstWhere('date', '2026-11-20')['arrivals'] ?? [];
    expect($arrivals)->toBe([]);
});

it('calcule le revenu par nuit sur les seuls séjours vendus et payés', function () {
    soldBooking($this->cocon, '2026-11-02', '2026-11-04', 15500);   // 2 nuits, 19 500 hors taxe
    // Annulée, 30 000 gardés : un revenu, mais aucune nuit vendue.
    $cancelled = directBooking($this->nid, '2026-11-10', '2026-11-14', BookingStatus::Cancelled);
    $cancelled->update(['total_amount' => 30000, 'tourist_tax' => 1000]);
    recordPayment($cancelled, 30000);
    // Saisie à la main pas encore payée : des nuits sans revenu encore.
    directBooking($this->nid, '2026-11-20', '2026-11-25')->update(['total_amount' => 50000, 'tourist_tax' => 1000]);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.totals.revenue', 49500)
        ->assertJsonPath('data.totals.revenue_per_night', 9750);
});
