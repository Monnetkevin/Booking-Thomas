<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\IcalFeed;
use App\Models\PlatformPayout;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Les tests fonctionnels tournent sur PostgreSQL (base booking_test) : la contrainte
| d'exclusion anti-chevauchement n'existe pas sous SQLite.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Tests unitaires : application démarrée (configuration, traductions, casts des modèles),
// sans base de données ; les dépôts sont simulés.
pest()->extend(TestCase::class)->in('Unit');

/** Corps valide pour POST/PUT /api/admin/apartments. */
function apartmentPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
        'description' => ['fr' => 'Bohème, rotin et lumière.', 'en' => ''],
        'address' => '12 rue de la Paix',
        'postal_code' => '72000',
        'city' => 'Le Mans',
        'capacity' => 4,
        'bedrooms' => 1,
        'beds' => 2,
        'bathrooms' => 1,
        'surface_m2' => 60,
        'check_in_time' => '16:00',
        'check_out_time' => '10:00',
        'min_stay' => 2,
        'max_stay' => 28,
        'turnover_days' => 0,
        'cleaning_fee' => 4500,
        'cancellation_policy' => 'moderate',
    ], $overrides);
}

/** Réservation directe et le bloc de ses nuits [arrivée, départ[. */
function directBooking(Apartment $apartment, string $arrival, string $departure, BookingStatus $status = BookingStatus::Confirmed): Booking
{
    $factory = Booking::factory()->for($apartment);
    $booking = ($status === BookingStatus::Pending ? $factory->pending() : $factory)
        ->create(['status' => $status, 'arrival' => $arrival, 'departure' => $departure]);
    AvailabilityBlock::factory()->for($apartment)->between($arrival, $departure)
        ->create(['type' => BlockType::Booking, 'booking_id' => $booking->id]);

    return $booking;
}

/** Paiement (ou remboursement) réussi d'une réservation : ce qui compte dans les revenus. */
function recordPayment(Booking $booking, int $amount, PaymentType $type = PaymentType::Payment): void
{
    $booking->payments()->create(['type' => $type, 'amount' => $amount, 'status' => PaymentStatus::Succeeded]);
}

/** Contenu d'un calendrier d'exemple de tests/fixtures/ical. */
function icalFixture(string $name): string
{
    return (string) file_get_contents(__DIR__."/fixtures/ical/$name.ics");
}

/** Bloc en mémoire (non enregistré) pour les tests unitaires. */
function memoryBlock(int $apartmentId, string $from, string $to, BlockType $type = BlockType::Manual, array $attributes = []): AvailabilityBlock
{
    return (new AvailabilityBlock)->forceFill(['apartment_id' => $apartmentId, 'type' => $type, 'starts_on' => $from, 'ends_on' => $to, 'is_active' => true, ...$attributes]);
}

/** Bloc importé par iCal en mémoire, avec son calendrier. */
function memoryIcalBlock(int $apartmentId, string $from, string $to, string $provider = 'airbnb', ?string $summary = 'Reserved'): AvailabilityBlock
{
    $block = memoryBlock($apartmentId, $from, $to, BlockType::Ical, ['note' => $summary]);

    return $block->setRelation('icalFeed', (new IcalFeed)->forceFill(['apartment_id' => $apartmentId, 'provider' => $provider]));
}

/** Réservation directe en mémoire, avec son bloc. */
function memoryBooking(int $apartmentId, string $arrival, string $departure, BookingStatus $status, array $attributes = []): Booking
{
    return (new Booking)->forceFill(['apartment_id' => $apartmentId, 'arrival' => $arrival, 'departure' => $departure, 'status' => $status, 'tourist_tax' => 0, ...$attributes]);
}

/** Versement de plateforme en mémoire. */
function memoryPayout(string $platform, int $apartmentId, string $arrival, int $nights, int $net): PlatformPayout
{
    $arrivalDate = CarbonImmutable::parse($arrival);

    return (new PlatformPayout)->forceFill([
        'platform' => $platform, 'apartment_id' => $apartmentId, 'arrival' => $arrival,
        'departure' => $arrivalDate->addDays($nights)->toDateString(), 'nights' => $nights, 'net_amount' => $net,
    ]);
}
