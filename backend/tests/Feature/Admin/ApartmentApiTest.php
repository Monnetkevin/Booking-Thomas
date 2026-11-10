<?php

use App\Enums\ApartmentStatus;
use App\Enums\BookingStatus;
use App\Models\Amenity;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\RatePeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Queue::fake();
    $this->actingAs(User::factory()->create());
});

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

function addPhoto(Apartment $apartment): void
{
    $apartment->addMedia(UploadedFile::fake()->image('salon.jpg', 1200, 800))
        ->toMediaCollection(Apartment::PHOTOS);
}

describe('création et modification', function () {
    it('crée un logement en brouillon avec un slug tiré du nom', function () {
        $amenities = Amenity::factory()->count(2)->create();

        $response = $this->postJson('/api/admin/apartments', apartmentPayload([
            'amenity_ids' => $amenities->pluck('id')->all(),
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'le-cocon')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.name.en', 'The Cocoon')
            ->assertJsonPath('data.cleaning_fee', 4500)
            ->assertJsonPath('data.check_in_time', '16:00')
            ->assertJsonCount(2, 'data.amenity_ids');

        // Traduction vide ignorée : l'anglais retombera sur le français.
        expect(Apartment::sole()->getTranslations('description'))->toBe(['fr' => 'Bohème, rotin et lumière.']);
    });

    it('rend le slug unique', function () {
        Apartment::factory()->create(['slug' => 'le-cocon']);

        $this->postJson('/api/admin/apartments', apartmentPayload())->assertJsonPath('data.slug', 'le-cocon-2');
    });

    it('place un nouveau logement en fin de liste', function () {
        Apartment::factory()->create(['position' => 4]);

        $this->postJson('/api/admin/apartments', apartmentPayload())->assertJsonPath('data.position', 5);
    });

    it('valide les champs en français', function () {
        $this->postJson('/api/admin/apartments', apartmentPayload([
            'name' => ['fr' => ''],
            'postal_code' => '72',
            'min_stay' => 5,
            'max_stay' => 3,
            'check_in_time' => '25:00',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['postal_code', 'max_stay', 'check_in_time'])
            ->assertJsonValidationErrors(['name.fr' => 'Le champ nom (français) est obligatoire.']);
    });

    it('modifie un logement sans toucher à son statut', function () {
        $apartment = Apartment::factory()->create(['status' => ApartmentStatus::Published]);

        $this->putJson("/api/admin/apartments/{$apartment->id}", apartmentPayload([
            'slug' => 'nouveau-nom',
            'capacity' => 6,
            'status' => 'draft',
        ]))
            ->assertOk()
            ->assertJsonPath('data.slug', 'nouveau-nom')
            ->assertJsonPath('data.capacity', 6)
            ->assertJsonPath('data.status', 'published');
    });

    it('liste les logements dans l’ordre, filtrés par statut, avec le prix de la période en cours', function () {
        $draft = Apartment::factory()->draft()->create(['position' => 1]);
        $published = Apartment::factory()->create(['position' => 0]);
        RatePeriod::factory()->for($published)->create([
            'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonth(), 'nightly_price' => 8500,
        ]);

        $this->getJson('/api/admin/apartments')
            ->assertOk()
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.base_price', 8500)
            ->assertJsonPath('data.1.id', $draft->id)
            ->assertJsonPath('data.1.base_price', null);

        $this->getJson('/api/admin/apartments?status=draft')->assertJsonCount(1, 'data');
    });

    it('réordonne les logements', function () {
        [$a, $b] = Apartment::factory()->count(2)->create();

        $this->putJson('/api/admin/apartments/order', ['ids' => [$b->id, $a->id]])->assertNoContent();

        expect($b->fresh()->position)->toBe(0)->and($a->fresh()->position)->toBe(1);
    });
});

describe('publication', function () {
    it('refuse de publier un logement incomplet en listant ce qui manque', function () {
        $apartment = Apartment::factory()->draft()->create(['description' => ['fr' => '']]);

        $this->postJson("/api/admin/apartments/{$apartment->id}/publish")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'apartment_not_publishable')
            ->assertJsonPath('details.missing.*.key', ['description', 'photos', 'rates']);

        expect($apartment->fresh()->status)->toBe(ApartmentStatus::Draft);
    });

    it('publie un logement complet puis le repasse en brouillon', function () {
        $apartment = Apartment::factory()->draft()->create();
        addPhoto($apartment);
        RatePeriod::factory()->for($apartment)->create([
            'starts_on' => now()->addDay(), 'ends_on' => now()->addMonths(3),
        ]);

        $this->postJson("/api/admin/apartments/{$apartment->id}/publish")->assertOk()->assertJsonPath('data.status', 'published');
        $this->postJson("/api/admin/apartments/{$apartment->id}/unpublish")->assertOk()->assertJsonPath('data.status', 'draft');
    });

    it('ne compte pas une période tarifaire déjà terminée', function () {
        $apartment = Apartment::factory()->draft()->create();
        addPhoto($apartment);
        RatePeriod::factory()->for($apartment)->create(['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']);

        $this->postJson("/api/admin/apartments/{$apartment->id}/publish")
            ->assertJsonPath('details.missing.*.key', ['rates']);
    });
});

describe('suppression', function () {
    it('supprime un logement sans séjour à venir en gardant son historique', function () {
        $apartment = Apartment::factory()->create(['slug' => 'le-cocon']);
        $past = Booking::factory()->for($apartment)->create([
            'arrival' => now()->subDays(10), 'departure' => now()->subDays(7), 'status' => BookingStatus::Completed,
        ]);

        $this->deleteJson("/api/admin/apartments/{$apartment->id}")->assertNoContent();

        $this->assertSoftDeleted($apartment);
        $this->assertModelExists($past);
        $this->getJson('/api/admin/apartments')->assertJsonCount(0, 'data');
        $this->getJson("/api/admin/apartments/{$apartment->id}")->assertNotFound();
        // Le slug d'un logement supprimé n'est pas réattribué.
        $this->postJson('/api/admin/apartments', apartmentPayload())->assertJsonPath('data.slug', 'le-cocon-2');
    });

    it('refuse de supprimer un logement qui a un séjour à venir', function () {
        $apartment = Apartment::factory()->create();
        Booking::factory()->for($apartment)->create();

        $this->deleteJson("/api/admin/apartments/{$apartment->id}")
            ->assertConflict()
            ->assertJsonPath('code', 'apartment_has_upcoming_stays');

        $this->assertModelExists($apartment);
    });

    it('ignore les réservations expirées ou annulées', function () {
        $apartment = Apartment::factory()->create();
        Booking::factory()->for($apartment)->create(['status' => BookingStatus::Cancelled]);

        $this->deleteJson("/api/admin/apartments/{$apartment->id}")->assertNoContent();
        $this->assertSoftDeleted($apartment);
    });
});

describe('droits', function () {
    it('refuse le rôle ménage', function () {
        $this->actingAs(User::factory()->housekeeping()->create());

        $this->getJson('/api/admin/apartments')->assertForbidden();
    });

    it('refuse les visiteurs', function () {
        auth()->guard('web')->logout();

        $this->getJson('/api/admin/apartments')->assertUnauthorized();
    });
});
