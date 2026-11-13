<?php

use App\Jobs\SyncIcalFeed;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
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

Artisan::command('bookings:confirm {reference}', function (BookingService $bookings, BookingRepositoryInterface $repository, string $reference) {
    $booking = $repository->findByReference($reference);
    if ($booking === null) {
        $this->error("Aucune réservation $reference.");

        return 1;
    }
    $bookings->confirm($booking);
    $this->info("Réservation $reference : {$booking->status->value}. E-mails de confirmation envoyés s'ils ne l'étaient pas déjà.");

    return 0;
})->purpose('Confirme une réservation en attente sans paiement (tests, en attendant Stripe)');

Artisan::command('ical:sync', function (IcalFeedRepositoryInterface $feeds) {
    $importable = $feeds->importable();
    $importable->each(fn ($feed) => SyncIcalFeed::dispatch($feed));
    $this->info("Synchronisation programmée pour {$importable->count()} calendrier(s).");
})->purpose('Relit les calendriers des plateformes (Airbnb) de tous les logements');

// Réservations Airbnb bloquées sur le site 15 minutes au plus après leur création.
Schedule::command('ical:sync')->everyFifteenMinutes()->withoutOverlapping();

// Paiement non finalisé dans les 40 minutes : nuits libérées dans la minute qui suit.
Schedule::command('bookings:expire')->everyMinute()->withoutOverlapping();
