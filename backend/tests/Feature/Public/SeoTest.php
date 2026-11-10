<?php

use App\Models\Apartment;

beforeEach(fn () => config(['booking.frontend_url' => 'https://exemple.fr']));

it('liste les pages et les fiches publiées en français et en anglais', function () {
    Apartment::factory()->create(['slug' => 'le-cocon']);
    Apartment::factory()->draft()->create(['slug' => 'brouillon']);

    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = simplexml_load_string($response->getContent());
    $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

    expect($locs)->toContain('https://exemple.fr/', 'https://exemple.fr/en', 'https://exemple.fr/logements/le-cocon', 'https://exemple.fr/en/logements/le-cocon', 'https://exemple.fr/cgv')
        ->not->toContain('https://exemple.fr/logements/brouillon');
    expect($response->getContent())->toContain('hreflang="en" href="https://exemple.fr/en/logements/le-cocon"');
});

it('indique le plan du site et ferme l’admin aux robots', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: https://exemple.fr/sitemap.xml');
});
