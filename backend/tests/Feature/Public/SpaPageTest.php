<?php

use App\Models\Apartment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    config([
        'app.name' => 'Les petits cocons du Mans',
        'booking.frontend_url' => 'https://exemple.fr',
        'media-library.queue_conversions_by_default' => false,
    ]);

    // index.html tel que le construit Vite.
    $this->index = tempnam(sys_get_temp_dir(), 'spa');
    file_put_contents($this->index, '<!doctype html><html lang="fr"><head><meta charset="UTF-8" /><title>Les petits cocons du Mans</title><script type="module" src="/assets/index-abc.js"></script></head><body><div id="root"></div></body></html>');
    config(['booking.spa_index' => $this->index]);
});

afterEach(fn () => @unlink($this->index));

it('sert la fiche d’un logement avec son nom et sa photo, sans JavaScript', function () {
    $cocon = Apartment::factory()->create([
        'slug' => 'le-cocon',
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
        'tagline' => ['fr' => 'Bohème, rotin et lumière', 'en' => 'Boho, rattan and light'],
    ]);
    $cocon->addMedia(UploadedFile::fake()->image('salon.jpg', 1600, 1067))
        ->withCustomProperties(['width' => 1600, 'height' => 1067])
        ->toMediaCollection(Apartment::PHOTOS);

    $html = $this->get('/logements/le-cocon')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-cache, private')
        ->getContent();

    expect($html)
        ->toContain('<title data-server-meta>Le Cocon · Les petits cocons du Mans</title>')
        ->toContain('name="description" content="Bohème, rotin et lumière"')
        ->toContain('rel="canonical" href="https://exemple.fr/logements/le-cocon"')
        ->toContain('hreflang="en" href="https://exemple.fr/en/logements/le-cocon"')
        ->toMatch('#property="og:image" content="[^"]+\.webp"#')
        ->toContain('src="/assets/index-abc.js"')
        ->and(substr_count($html, '<title'))->toBe(1);

    $this->get('/en/logements/le-cocon')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('The Cocoon · Les petits cocons du Mans')
        ->assertSee('property="og:locale" content="en_GB"', false);
});

it('donne à chaque page fixe son titre et sa description', function () {
    $this->get('/')->assertOk()->assertSee('Votre pied-à-terre au Mans · Les petits cocons du Mans');
    $this->get('/en')->assertOk()->assertSee('Your base in Le Mans');
    $this->get('/contact/')->assertOk()->assertSee('Contact · Les petits cocons du Mans')
        ->assertSee('href="https://exemple.fr/contact"', false);
    $this->get('/en/cgv')->assertOk()->assertSee('Terms and conditions');
});

it('répond 404 sans indexation pour une page ou un logement inconnu', function () {
    Apartment::factory()->draft()->create(['slug' => 'brouillon']);

    foreach (['/logements/brouillon', '/logements/inconnu', '/page-inexistante'] as $uri) {
        $this->get($uri)->assertNotFound()
            ->assertSee('Page introuvable · Les petits cocons du Mans')
            ->assertSee('name="robots" content="noindex"', false);
    }
});

it('n’indexe pas les liens personnels reçus par e-mail', function () {
    $this->get('/reservation/BT-ABC123?signature=x')->assertOk()->assertSee('name="robots" content="noindex"', false);
    $this->get('/avis/jeton')->assertOk()->assertSee('name="robots" content="noindex"', false);
});

it('laisse l’API, l’admin, la santé et les calendriers hors des pages du site', function () {
    $this->getJson('/api/route-inconnue')->assertNotFound()->assertJsonStructure(['message']);
    $this->get('/admin')->assertNotFound();
    $this->get('/up')->assertOk()->assertDontSee('data-server-meta', false);
    $this->get('/ical/court.ics')->assertNotFound()->assertDontSee('data-server-meta', false);
});
