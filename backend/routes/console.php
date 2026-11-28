<?php

use App\Jobs\SyncIcalFeed;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\IcalSyncLog;
use App\Models\Notification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use App\Services\Apartment\ApartmentService;
use App\Services\Booking\BookingService;
use App\Services\Housekeeping\HousekeepingNotifier;
use App\Services\Privacy\GuestPrivacyService;
use App\Services\Review\ReviewInvitationService;
use Carbon\CarbonImmutable;
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

Artisan::command('reviews:invite', function (ReviewInvitationService $reviews) {
    $completed = $reviews->completeDepartedStays();
    $invited = $reviews->sendDueInvitations();
    $this->info("$completed séjour(s) terminé(s), $invited invitation(s) à laisser un avis envoyée(s).");
})->purpose('Termine les séjours passés et invite les voyageurs partis la veille à laisser un avis');

Artisan::command('privacy:anonymize', function (GuestPrivacyService $privacy) {
    $count = $privacy->anonymizeExpired(CarbonImmutable::now());
    $this->info("$count réservation(s) anonymisée(s) (départ il y a plus de ".GuestPrivacyService::KEEP_YEARS.' ans).');
})->purpose('Anonymise les voyageurs partis depuis plus de 3 ans (RGPD)');

Artisan::command('ical:sync', function (IcalFeedRepositoryInterface $feeds) {
    $importable = $feeds->importable();
    $importable->each(fn ($feed) => SyncIcalFeed::dispatch($feed));
    $this->info("Synchronisation programmée pour {$importable->count()} calendrier(s).");
})->purpose('Relit les calendriers des plateformes (Airbnb) de tous les logements');

Artisan::command('notifications:housekeeping-summary', function (HousekeepingNotifier $housekeeping) {
    $count = $housekeeping->sendDailySummaries(CarbonImmutable::now());
    $this->info("Récapitulatif du lendemain envoyé à $count compte(s) ménage.");
})->purpose('Envoie aux comptes ménage, à leur heure, les départs et arrivées du lendemain');

// Réservations Airbnb bloquées sur le site 15 minutes au plus après leur création.
// Verrou de 30 minutes (24 h par défaut) : un conteneur tué en pleine synchro ne la bloque pas.
Schedule::command('ical:sync')->everyFifteenMinutes()->withoutOverlapping(30);

// Journal des synchronisations conservé 30 jours, notifications lues 90 jours, journal d'audit
// et messages de contact un an.
Schedule::command('model:prune', ['--model' => [IcalSyncLog::class, Notification::class, AuditLog::class, ContactMessage::class]])->daily();

// RGPD : voyageurs partis depuis plus de 3 ans anonymisés, le 1er de chaque mois.
Schedule::command('privacy:anonymize')->monthlyOn(1, '03:00');

// Récapitulatif du ménage : chaque heure pile, aux comptes dont c'est l'heure choisie.
Schedule::command('notifications:housekeeping-summary')->hourly();

// Paiement non finalisé dans les 40 minutes : nuits libérées dans la minute qui suit.
// Verrou de 5 minutes (24 h par défaut) : un conteneur tué en cours ne bloque pas les expirations.
Schedule::command('bookings:expire')->everyMinute()->withoutOverlapping(5);

// Séjours terminés et invitations à laisser un avis, le lendemain du départ.
Schedule::command('reviews:invite')->dailyAt('10:00')->withoutOverlapping(30);
