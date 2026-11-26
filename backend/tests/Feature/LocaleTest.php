<?php

use App\Models\Apartment;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('api')->get('/api/_test/locale', function () {
        $apartment = Apartment::firstOrFail();

        return ['locale' => app()->getLocale(), 'name' => $apartment->name, 'error' => __('booking.dates_unavailable')];
    });

    Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
});

it('répond en français sans préférence de langue', function () {
    $this->getJson('/api/_test/locale', ['Accept-Language' => ''])
        ->assertJson(['locale' => 'fr', 'name' => 'Le Cocon'])
        ->assertHeader('Content-Language', 'fr');
});

it('répond en anglais quand la SPA le demande', function () {
    $this->getJson('/api/_test/locale', ['Accept-Language' => 'en'])
        ->assertJson(['locale' => 'en', 'name' => 'The Cocoon', 'error' => 'These dates are no longer available. Please choose other dates.'])
        ->assertHeader('Content-Language', 'en');
});

it('retombe sur le français pour une langue non gérée', function () {
    $this->getJson('/api/_test/locale', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertJson(['locale' => 'fr']);
});

it('retombe sur le français quand une traduction manque', function () {
    Apartment::query()->delete();
    Apartment::factory()->create(['name' => ['fr' => 'Nid doux']]);

    $this->getJson('/api/_test/locale', ['Accept-Language' => 'en'])->assertJson(['name' => 'Nid doux']);
});
