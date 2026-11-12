<?php

use App\Services\Apartment\ApartmentService;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('apartments:geocode', function (ApartmentService $apartments) {
    $count = $apartments->geocodeMissing();
    $this->info("Calcul de position programmé pour $count logement(s).");
})->purpose('Calcule la position sur la carte des logements qui n’en ont pas encore');

Artisan::command('bookings:expire', function (BookingService $bookings) {
    $count = $bookings->expireOverdue();
    $this->info("$count réservation(s) expirée(s), nuits libérées.");
})->purpose('Expire les réservations en attente non payées à temps et libère leurs nuits');

// Paiement non finalisé dans les 30 minutes : nuits libérées dans la minute qui suit.
Schedule::command('bookings:expire')->everyMinute()->withoutOverlapping();
