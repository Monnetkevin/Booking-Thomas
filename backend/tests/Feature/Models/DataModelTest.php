<?php

use App\Enums\BookingStatus;
use App\Models\Amenity;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\IcalFeed;
use App\Models\RatePeriod;
use Illuminate\Support\Facades\DB;

it('traduit le nom et la description des logements', function () {
    $apartment = Apartment::factory()->create([
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
    ]);

    expect($apartment->getTranslation('name', 'fr'))->toBe('Le Cocon')
        ->and($apartment->getTranslation('name', 'en'))->toBe('The Cocoon');
});

it('relie logements, équipements et périodes tarifaires', function () {
    $apartment = Apartment::factory()->create();
    $apartment->amenities()->attach(Amenity::factory()->count(2)->create());
    RatePeriod::factory()->for($apartment)->create();

    expect($apartment->amenities)->toHaveCount(2)
        ->and($apartment->ratePeriods)->toHaveCount(1);
});

it('compte les nuits d’une réservation', function () {
    $booking = Booking::factory()->create();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->nights())->toBe(3);
});

it('chiffre l’URL d’import iCal et masque les secrets du flux', function () {
    $feed = IcalFeed::factory()->create(['import_url' => 'https://www.airbnb.fr/calendar/ical/123.ics?s=secret']);

    $stored = DB::table('ical_feeds')->where('id', $feed->id)->value('import_url');

    expect($stored)->not->toContain('airbnb')
        ->and($feed->fresh()->import_url)->toContain('airbnb')
        ->and($feed->toArray())->not->toHaveKeys(['import_url', 'export_token']);
});

it('trouve les blocs qui touchent une période', function () {
    $apartment = Apartment::factory()->create();
    AvailabilityBlock::factory()->for($apartment)->between('2027-03-10', '2027-03-13')->create();

    // Le départ du 13 libère la nuit du 13 : un séjour arrivant ce jour-là ne chevauche pas.
    expect(AvailabilityBlock::overlapping('2027-03-13', '2027-03-15')->count())->toBe(0)
        ->and(AvailabilityBlock::overlapping('2027-03-12', '2027-03-14')->count())->toBe(1);
});
