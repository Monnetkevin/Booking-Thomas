<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\PlatformMonthlyRevenue;
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
        // Mois en cours : pas encore de montant à saisir.
        ->assertJsonPath('data.months.11.platform_revenue_missing', false)
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

it('ajoute les revenus saisis des plateformes, la part du direct, et change de période', function () {
    soldBooking($this->cocon, '2026-11-02', '2026-11-04', 30000);
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM2']);
    monthlyRevenue($this->nid, 'airbnb', '2026-11', 20000);

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

it('signale les revenus manquants plateforme par plateforme, sur les mois écoulés', function () {
    // Nuits vendues sur Airbnb en octobre, mais seul un montant Booking.com saisi.
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2026-10-10', '2026-10-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM3']);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM4']);
    monthlyRevenue($this->cocon, 'booking', '2026-10', 15000);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.4.platform_revenue', ['airbnb' => 0, 'booking' => 15000])
        ->assertJsonPath('data.months.4.platform_revenue_missing', true)
        // Novembre, en cours, n'est pas encore à saisir.
        ->assertJsonPath('data.months.5.platform_revenue_missing', false)
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
    monthlyRevenue($this->nid, 'booking', '2026-11', 30000);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.totals.total_revenue', 49500)
        ->assertJsonPath('data.totals.revenue_per_night', 9750);
});

it('attend un montant pour chaque mois que touche un séjour à cheval', function () {
    Date::setTestNow('2027-03-10 10:00:00');
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2027-01-28', '2027-02-05')->create(['type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM9']);
    monthlyRevenue($this->nid, 'airbnb', '2027-01', 40000);

    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.3.month', '2027-01')
        ->assertJsonPath('data.months.3.nights_platform', 4)
        ->assertJsonPath('data.months.3.platform_revenue_missing', false)
        ->assertJsonPath('data.months.4.month', '2027-02')
        ->assertJsonPath('data.months.4.nights_platform', 4)
        ->assertJsonPath('data.months.4.platform_revenue_missing', true);
});

it('compte les dates fermées sur Airbnb comme fermées, et un bloc Booking.com comme vendu', function () {
    $airbnb = IcalFeed::factory()->for($this->nid)->create();
    $booking = IcalFeed::factory()->for($this->cocon)->create(['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/ical.ics?t=x']);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-12')->create(['type' => BlockType::Ical, 'ical_feed_id' => $airbnb->id, 'external_uid' => 'A1', 'note' => 'Reserved']);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-20', '2026-11-30')->create(['type' => BlockType::Ical, 'ical_feed_id' => $airbnb->id, 'external_uid' => 'A2', 'note' => 'Airbnb (Not available)']);
    // Booking.com : même résumé pour une réservation et une fermeture ; compté comme un séjour.
    AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-03', '2026-11-06')->create(['type' => BlockType::Ical, 'ical_feed_id' => $booking->id, 'external_uid' => 'B1', 'note' => 'CLOSED - Not available']);
    AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-15', '2026-11-25')->create(['type' => BlockType::Ical, 'ical_feed_id' => $booking->id, 'external_uid' => 'B2', 'note' => 'CLOSED - Not available']);
    monthlyRevenue($this->nid, 'airbnb', '2026-11', 20000);
    monthlyRevenue($this->cocon, 'booking', '2026-11', 45000);

    // Novembre : 2 logements × 30 nuits, 10 fermées (Airbnb), 15 vendues (2 Airbnb, 13 Booking.com).
    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.5.nights_platform', 15)
        ->assertJsonPath('data.months.5.occupancy', 30)
        ->assertJsonPath('data.months.5.platform_revenue', ['airbnb' => 20000, 'booking' => 45000])
        ->assertJsonPath('data.totals.revenue_per_night', 4333)
        ->assertJsonPath('data.totals.average_stay', 5);
    // Le tableau de bord ne montre pas une date fermée sur Airbnb comme une arrivée.
    $dashboard = $this->getJson('/api/admin/dashboard')
        // Mêmes règles que les statistiques.
        ->assertJsonPath('data.figures.occupancy.value', 30)
        ->assertJsonPath('data.figures.stays.value', 3);
    $arrivals = collect($dashboard->json('data.movements'))->firstWhere('date', '2026-11-20')['arrivals'] ?? [];
    expect($arrivals)->toBe([]);
    $this->getJson('/api/admin/planning?from=2026-11-01&days=30')
        ->assertJsonPath('data.figures.sold_nights', 15)
        ->assertJsonPath('data.figures.open_nights', 50)
        ->assertJsonPath('data.figures.arrivals', 3);
    // Le Cocon : 13 nuits vendues sur 30 ouvertes.
    $this->getJson('/api/admin/apartments')->assertJsonPath("meta.occupancy.{$this->cocon->id}", 43.3);
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

/** Net versé saisi pour une plateforme, un logement et un mois (« 2026-11 »). */
function monthlyRevenue(Apartment $apartment, string $platform, string $month, int $net): void
{
    PlatformMonthlyRevenue::query()->create(['platform' => $platform, 'apartment_id' => $apartment->id, 'month' => $month.'-01', 'net_amount' => $net]);
}

it('laisse les brouillons hors de l’occupation, mais garde leurs séjours vendus', function () {
    // Le Cocon : 3 nuits vendues en novembre.
    soldBooking($this->cocon, '2026-11-02', '2026-11-05', 30000);
    // Le Nid devient un brouillon : ni ses nuits ni ses nuits ouvertes ne comptent dans l'occupation.
    $this->nid->update(['status' => 'draft']);
    soldBooking($this->nid, '2026-11-10', '2026-11-20', 90000);
    // Logement publié créé le 21 novembre : compte tout le mois (imprécision assumée).
    $recent = Apartment::factory()->create(['name' => ['fr' => 'Récent', 'en' => 'Recent'], 'position' => 3, 'created_at' => '2026-11-21 10:00:00']);
    soldBooking($recent, '2026-11-25', '2026-11-27', 20000);

    // (3 + 2) vendues sur 2 × 30 nuits ouvertes.
    $this->getJson('/api/admin/statistics?period=six_months')
        ->assertJsonPath('data.months.5.occupancy', 8.3)
        ->assertJsonPath('data.months.5.nights_direct', 15)
        ->assertJsonCount(3, 'data.apartments')
        ->assertJsonPath('data.apartments.1.occupancy', null);
    $this->getJson('/api/admin/dashboard')->assertJsonPath('data.figures.occupancy.value', 8.3);
    // Planning : les séjours du brouillon restent vendus, ses nuits ne sont ni ouvertes ni libres.
    $this->getJson('/api/admin/planning?from=2026-11-01&days=30')
        ->assertJsonPath('data.figures.sold_nights', 15)
        ->assertJsonPath('data.figures.arrivals', 3)
        ->assertJsonPath('data.figures.open_nights', 60)
        ->assertJsonPath('data.figures.free_nights', 55);
    $this->getJson('/api/admin/apartments')->assertJsonPath("meta.occupancy.{$recent->id}", 6.7);
});

it('garde les revenus et les séjours d’un logement dépublié, hors de l’occupation', function () {
    soldBooking($this->nid, '2026-10-10', '2026-10-14', 40000);
    soldBooking($this->nid, '2026-11-02', '2026-11-04', 20000);
    // Dépublié pour l'intersaison : l'argent encaissé ne disparaît pas.
    $this->nid->update(['status' => 'draft']);

    $this->getJson('/api/admin/statistics')
        ->assertJsonPath('data.totals.revenue', 68000)
        ->assertJsonPath('data.months.10.nights_direct', 4)
        ->assertJsonPath('data.months.10.revenue', 44000)
        // Seul Le Cocon est sur le marché : rien de vendu.
        ->assertJsonPath('data.months.10.occupancy', 0)
        ->assertJsonCount(2, 'data.apartments')
        ->assertJsonPath('data.apartments.1.revenue', 68000)
        ->assertJsonPath('data.apartments.1.occupancy', null);
    $this->getJson('/api/admin/dashboard')
        ->assertJsonPath('data.figures.revenue.value', 24000)
        ->assertJsonPath('data.figures.stays.value', 1)
        ->assertJsonPath('data.figures.occupancy.value', 0);
});
