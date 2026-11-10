<?php

use App\Models\Amenity;
use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogPresenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Date::setTestNow('2026-11-10');
    Storage::fake('public');
    Storage::fake('local');
    config(['media-library.queue_conversions_by_default' => false]);

    $this->cocon = Apartment::factory()->create([
        'slug' => 'le-cocon',
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
        'tagline' => ['fr' => 'Bohème, rotin et lumière'],
        'address' => '12 bis rue de la Paix',
        'latitude' => 48.0103421,
        'longitude' => 0.1902345,
        'position' => 1,
    ]);
    $this->cocon->amenities()->attach(Amenity::factory()->create(['key' => 'wifi', 'label' => ['fr' => 'Wi-Fi', 'en' => 'Wi-Fi']]));
    $this->cocon->addMedia(UploadedFile::fake()->image('salon.jpg', 1600, 1067))
        ->withCustomProperties(['alt' => ['fr' => 'Salon', 'en' => 'Living room'], 'width' => 1600, 'height' => 1067])
        ->toMediaCollection(Apartment::PHOTOS);
    RatePeriod::factory()->for($this->cocon)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'nightly_price' => 9500]);
    RatePeriod::factory()->for($this->cocon)->create(['starts_on' => '2026-12-01', 'ends_on' => '2027-01-04', 'nightly_price' => 8000, 'min_stay' => null]);
    RatePeriod::factory()->for($this->cocon)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-01-31', 'nightly_price' => 5000]);

    Apartment::factory()->draft()->create(['slug' => 'brouillon']);
});

it('liste les logements publiés seulement, dans la langue demandée', function () {
    Apartment::factory()->create(['slug' => 'nid-doux', 'position' => 0]);

    $this->getJson('/api/apartments', ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'nid-doux')
        ->assertJsonPath('data.1.name', 'The Cocoon')
        ->assertJsonPath('data.1.tagline', 'Bohème, rotin et lumière')
        ->assertJsonPath('data.1.price_from', 8000)
        ->assertJsonPath('data.1.amenities.0.key', 'wifi')
        ->assertJsonPath('data.1.cover.alt', 'Living room')
        ->assertJsonMissingPath('data.1.address');
});

it('renvoie la fiche sans l’adresse exacte', function () {
    $response = $this->getJson('/api/apartments/le-cocon')->assertOk();

    $response->assertJsonPath('data.name', 'Le Cocon')
        ->assertJsonPath('data.location.street', 'rue de la Paix')
        ->assertJsonPath('data.location.latitude', 48.01)
        ->assertJsonPath('data.location.longitude', 0.19)
        ->assertJsonPath('data.cancellation.policy', 'moderate')
        ->assertJsonPath('data.rates.*.starts_on', ['2026-11-01', '2026-12-01'])
        ->assertJsonPath('data.rates.1.min_stay', 2)
        ->assertJsonPath('data.photos.0.sources.card.webp', fn ($url) => str_ends_with($url, '.webp'));

    expect(json_encode($response->json()))->not->toContain('12 bis');
});

it('donne les paliers d’annulation du modèle du logement', function () {
    app(SettingRepositoryInterface::class)->put('cancellation_policies', [
        'moderate' => [['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50]],
    ]);

    $this->getJson('/api/apartments/le-cocon')->assertJsonPath('data.cancellation.tiers.0.days_before', 5);
});

it('répond 404 pour un brouillon, un logement supprimé ou inconnu', function () {
    $this->getJson('/api/apartments/brouillon')->assertNotFound();
    $this->getJson('/api/apartments/inconnu')->assertNotFound();

    $this->cocon->delete();
    app(CatalogCache::class)->flush();
    $this->getJson('/api/apartments/le-cocon')->assertNotFound();
});

it('masque les photos dont les versions ne sont pas encore prêtes', function () {
    config(['media-library.queue_conversions_by_default' => true]);
    Queue::fake();
    $this->cocon->addMedia(UploadedFile::fake()->image('chambre.jpg', 1600, 1067))->toMediaCollection(Apartment::PHOTOS);
    app(CatalogCache::class)->flush();

    $this->getJson('/api/apartments/le-cocon')->assertJsonCount(1, 'data.photos');
});

it('autorise les caches HTTP une minute, par langue', function () {
    $response = $this->getJson('/api/apartments');

    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=60')
        ->and($response->headers->get('Vary'))->toContain('Accept-Language')
        ->and($response->headers->get('ETag'))->not->toBeNull();
});

it('met la liste en cache et la vide dès qu’un logement change dans l’admin', function () {
    $this->getJson('/api/apartments')->assertJsonPath('data.0.name', 'Le Cocon');

    // Modification directe en base : le cache sert encore l'ancienne version.
    Apartment::whereKey($this->cocon->id)->update(['name' => json_encode(['fr' => 'Cocon modifié'])]);
    $this->getJson('/api/apartments')->assertJsonPath('data.0.name', 'Le Cocon');

    // Modification par l'admin : cache vidé.
    $this->actingAs(User::factory()->create())
        ->putJson("/api/admin/apartments/{$this->cocon->id}", apartmentPayload(['name' => ['fr' => 'Le Cocon doré']]))
        ->assertOk();
    auth()->guard('web')->logout();

    $this->getJson('/api/apartments')->assertJsonPath('data.0.name', 'Le Cocon doré');
});

it('vide le cache quand un tarif change', function () {
    $this->getJson('/api/apartments')->assertJsonPath('data.0.price_from', 8000);

    $this->actingAs(User::factory()->create())
        ->postJson("/api/admin/apartments/{$this->cocon->id}/rate-periods", [
            'starts_on' => '2027-02-01', 'ends_on' => '2027-02-28', 'nightly_price' => 6000,
        ])->assertCreated();

    $this->getJson('/api/apartments')->assertJsonPath('data.0.price_from', 6000);
});

it('retire le numéro de rue des adresses', function (string $address, string $street) {
    expect(CatalogPresenter::streetWithoutNumber($address))->toBe($street);
})->with([
    ['12 rue de la Paix', 'rue de la Paix'],
    ['12 bis, rue de la Paix', 'rue de la Paix'],
    ['3B avenue Jean Jaurès', 'avenue Jean Jaurès'],
    ['Place de la République', 'Place de la République'],
]);

it('met en cache des données réellement sérialisées (Redis), sans objet PHP', function () {
    // Vrai stockage sérialisé : le cache en mémoire des tests ne sérialise rien.
    config(['cache.default' => 'redis']);
    app()->forgetInstance('cache.store');
    Cache::clearResolvedInstances();
    app(CatalogCache::class)->flush();

    $this->getJson('/api/apartments')->assertOk()->assertJsonPath('data.0.name', 'Le Cocon');
    expect(Cache::store('redis')->tags([CatalogCache::TAG])->get('summaries:fr'))->toBeArray();
    $this->getJson('/api/apartments')->assertOk()->assertJsonPath('data.0.name', 'Le Cocon');
    $this->getJson('/api/apartments/le-cocon')->assertOk();
    $this->getJson('/api/apartments/le-cocon')->assertOk()->assertJsonPath('data.location.street', 'rue de la Paix');
    $this->getJson('/api/apartments/inconnu')->assertNotFound();
    $this->getJson('/api/apartments/inconnu')->assertNotFound();

    app(CatalogCache::class)->flush();
})->skip(fn () => ! extension_loaded('redis'), 'Redis indisponible');
