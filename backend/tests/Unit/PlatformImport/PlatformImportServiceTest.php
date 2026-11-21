<?php

use App\Enums\PayoutPlatform;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\PlatformImport\Parsers\AirbnbCsvParser;
use App\Services\PlatformImport\Parsers\BookingCsvParser;
use App\Services\PlatformImport\PlatformImportService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->payouts = Mockery::mock(PlatformPayoutRepositoryInterface::class);
    $apartments = Mockery::mock(ApartmentRepositoryInterface::class);
    $apartments->allows('allOrdered')->andReturn(new Collection([
        (new Apartment)->forceFill(['id' => 1]),
        (new Apartment)->forceFill(['id' => 2]),
    ]));
    $this->service = new PlatformImportService($this->payouts, $apartments, new AirbnbCsvParser, new BookingCsvParser, Mockery::mock(AuditLogger::class));
    $this->payouts->allows('existingKeys')->andReturn([]);
});

const LATER_ADJUSTMENT = "Date;Type;Code de confirmation;Date de début;Nuits;Voyageur;Logement;Montant\n02/03/2027;Annulation;HMOLD;;;Jean;;-500,00\n";

it('donne à un ajustement tardif les dates du séjour déjà importé, et l’annonce seule rattachée à son logement', function () {
    $this->payouts->allows('listingMap')->andReturn(['Studio Cocon' => 1, 'Loft A' => 2, 'Loft B' => 2]);
    $this->payouts->expects('firstPayoutsByCode')->with(PayoutPlatform::Airbnb, ['HMOLD'])
        ->andReturn(['HMOLD' => ['arrival' => CarbonImmutable::parse('2026-11-04'), 'nights' => 3, 'apartment_id' => 1]]);

    $preview = $this->service->preview(PayoutPlatform::Airbnb, LATER_ADJUSTMENT);

    expect($preview['rows'])->toHaveCount(1)
        ->and($preview['rows'][0]['arrival'])->toBe('2026-11-04')
        ->and($preview['rows'][0]['departure'])->toBe('2026-11-07')
        ->and($preview['rows'][0]['listing'])->toBe('Studio Cocon')
        ->and($preview['listings'])->toBe([['listing' => 'Studio Cocon', 'apartment_id' => 1]]);
});

it('écarte l’ajustement tardif quand plusieurs annonces mènent au logement du séjour', function () {
    $this->payouts->allows('listingMap')->andReturn(['Loft A' => 2, 'Loft B' => 2]);
    $this->payouts->allows('firstPayoutsByCode')
        ->andReturn(['HMOLD' => ['arrival' => CarbonImmutable::parse('2026-11-04'), 'nights' => 3, 'apartment_id' => 2]]);

    $preview = $this->service->preview(PayoutPlatform::Airbnb, LATER_ADJUSTMENT);

    expect($preview['rows'])->toBe([])
        ->and($preview['skipped'])->toBe([['line' => 2, 'reason' => 'unreadable']]);
});

it('ne cherche pas les séjours déjà importés quand le fichier contient ses réservations', function () {
    $this->payouts->allows('listingMap')->andReturn([]);
    $this->payouts->expects('firstPayoutsByCode')->never();

    $preview = $this->service->preview(PayoutPlatform::Airbnb, "Date;Type;Code de confirmation;Date de début;Nuits;Voyageur;Logement;Montant\n05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00\n06/11/2026;Annulation;HM1;;;Jean;;-800,00\n");

    expect($preview['rows'])->toHaveCount(2);
});
