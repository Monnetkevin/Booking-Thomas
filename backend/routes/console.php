<?php

use App\Services\Apartment\ApartmentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('apartments:geocode', function (ApartmentService $apartments) {
    $count = $apartments->geocodeMissing();
    $this->info("Calcul de position programmé pour $count logement(s).");
})->purpose('Calcule la position sur la carte des logements qui n’en ont pas encore');
