<?php

use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\ApartmentController;
use App\Http\Controllers\Admin\ApartmentPhotoController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CleaningScheduleController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExtraController;
use App\Http\Controllers\Admin\IcalFeedController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PlanningController;
use App\Http\Controllers\Admin\PlatformRevenueController;
use App\Http\Controllers\Admin\QuotePreviewController;
use App\Http\Controllers\Admin\RatePeriodController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Public\ApartmentController as PublicApartmentController;
use App\Http\Controllers\Public\AvailabilityController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CouponController;
use App\Http\Controllers\Public\ExtraController as PublicExtraController;
use App\Http\Controllers\Public\QuoteController;
use App\Http\Controllers\Public\ReviewController as PublicReviewController;
use App\Http\Controllers\Public\ReviewInvitationController;
use Illuminate\Support\Facades\Route;

/*
| Site public : lecture seule, réponses en cache (Redis et en-têtes HTTP, une minute)
*/
Route::middleware(['throttle:public', 'cache.headers:public;max_age=60;etag'])->group(function () {
    Route::get('apartments', [PublicApartmentController::class, 'index']);
    Route::get('apartments/{slug}', [PublicApartmentController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('apartments/{slug}/availability', [AvailabilityController::class, 'calendar'])->where('slug', '[a-z0-9-]+');
    Route::get('reviews', [PublicReviewController::class, 'index']);
});

// Options d'un logement pour une arrivée (délai de commande du moment) : jamais en cache.
Route::get('apartments/{slug}/extras', [PublicExtraController::class, 'index'])->where('slug', '[a-z0-9-]+')->middleware('throttle:public');

// Recherche par dates : calculée à chaque fois (prix et disponibilités du moment).
Route::get('availability', [AvailabilityController::class, 'search'])->middleware('throttle:public');

// Formulaire de contact : 3 messages par minute et par IP, anti-robot.
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');

// Réservation en attente de paiement : 5 par minute et par IP, anti-robot.
Route::post('bookings', [BookingController::class, 'store'])->middleware('throttle:bookings');

// Suivi par le voyageur : lien signé reçu par e-mail (signature du chemin relatif).
Route::get('bookings/{booking:reference}', [BookingController::class, 'show'])
    ->middleware(['signed:relative', 'throttle:public'])->name('bookings.show');
Route::get('bookings/{booking:reference}/pdf', [BookingController::class, 'pdf'])
    ->middleware(['signed:relative', 'throttle:public'])->name('bookings.pdf');

// Avis du voyageur : lien personnel reçu après le séjour (jeton à usage unique).
Route::get('review-invitations/{token}', [ReviewInvitationController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:public');
Route::post('review-invitations/{token}', [ReviewInvitationController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1');

// Code promo saisi au paiement : 10 essais par minute et par IP (pas de devinette de codes).
Route::post('coupons/validate', [CouponController::class, 'validate'])->middleware('throttle:10,1');

// Devis d'un séjour (jamais mis en cache) : 30 requêtes par minute et par IP.
Route::post('quotes', [QuoteController::class, 'store'])->middleware('throttle:quotes');

/*
| Authentification (SPA, cookie de session Sanctum)
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [SessionController::class, 'store'])->middleware('throttle:10,1');
    // Mot de passe choisi depuis le lien d'invitation ou de réinitialisation.
    Route::post('password', [PasswordController::class, 'update'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [SessionController::class, 'show'])->middleware('role:admin,housekeeping');
        Route::post('logout', [SessionController::class, 'destroy']);
    });
});

/*
| Back-office, pour tous les rôles : notifications et préférences du compte connecté, planning du ménage
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,housekeeping'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification');
    Route::get('me/notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('me/notification-preferences', [NotificationController::class, 'updatePreferences']);

    // Planning du ménage : départs, arrivées et fermetures, sans donnée personnelle
    Route::get('cleaning-schedule', CleaningScheduleController::class);
});

/*
| Back-office : administrateurs (les Policies affinent). Tarifs, comptes et réglages sont
| regroupés plus bas.
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Logements (identifiés par leur id dans l'admin : le slug peut changer)
    Route::put('apartments/order', [ApartmentController::class, 'reorder']);
    Route::get('apartments', [ApartmentController::class, 'index']);
    Route::post('apartments', [ApartmentController::class, 'store']);
    Route::get('apartments/{apartment:id}', [ApartmentController::class, 'show']);
    Route::put('apartments/{apartment:id}', [ApartmentController::class, 'update']);
    Route::delete('apartments/{apartment:id}', [ApartmentController::class, 'destroy']);
    Route::post('apartments/{apartment:id}/publish', [ApartmentController::class, 'publish']);
    Route::post('apartments/{apartment:id}/unpublish', [ApartmentController::class, 'unpublish']);

    // Photos (la première est la couverture) et équipements
    Route::get('apartments/{apartment:id}/photos', [ApartmentPhotoController::class, 'index']);
    Route::post('apartments/{apartment:id}/photos', [ApartmentPhotoController::class, 'store']);
    Route::put('apartments/{apartment:id}/photos/order', [ApartmentPhotoController::class, 'reorder']);
    Route::patch('apartments/{apartment:id}/photos/{photo}', [ApartmentPhotoController::class, 'update'])->whereNumber('photo');
    Route::delete('apartments/{apartment:id}/photos/{photo}', [ApartmentPhotoController::class, 'destroy'])->whereNumber('photo');
    Route::get('amenities', [AmenityController::class, 'index']);
    Route::put('apartments/{apartment:id}/amenities', [AmenityController::class, 'sync']);

    // Aperçu du prix d'un séjour (réservation à la main)
    Route::post('apartments/{apartment:id}/quote-preview', QuotePreviewController::class);

    // Calendriers des plateformes (Airbnb) : import et export iCal
    Route::get('ical-feeds', [IcalFeedController::class, 'index']);
    Route::get('ical-sync-logs', [IcalFeedController::class, 'latestLogs']);
    // Tout synchroniser : en file d'attente, 2 fois par minute au plus.
    Route::post('ical-feeds/sync-all', [IcalFeedController::class, 'syncAll'])->middleware('throttle:2,1');
    Route::post('apartments/{apartment:id}/ical-feeds', [IcalFeedController::class, 'store']);
    Route::patch('ical-feeds/{icalFeed}', [IcalFeedController::class, 'update']);
    Route::post('ical-feeds/{icalFeed}/export-token', [IcalFeedController::class, 'regenerateExportToken']);
    Route::get('ical-feeds/{icalFeed}/logs', [IcalFeedController::class, 'logs']);
    // Lecture immédiate : 6 par minute, pour ne pas solliciter la plateforme à l'excès.
    Route::post('ical-feeds/{icalFeed}/sync', [IcalFeedController::class, 'sync'])->middleware('throttle:6,1');

    // Réservations : liste, fiche, coordonnées, annulation, confirmation (référence BT-…)
    Route::get('bookings', [AdminBookingController::class, 'index']);
    // Export CSV (noms des voyageurs) : 10 par minute, tracé au journal.
    Route::get('bookings.csv', [AdminBookingController::class, 'csv'])->middleware('throttle:10,1');
    Route::post('bookings', [AdminBookingController::class, 'store'])->middleware('throttle:20,1');
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);
    Route::patch('bookings/{booking}', [AdminBookingController::class, 'update']);
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel']);
    Route::post('bookings/{booking}/resend-confirmation', [AdminBookingController::class, 'resendConfirmation'])->middleware('throttle:6,1');
    Route::get('bookings/{booking}/pdf', [AdminBookingController::class, 'pdf']);

    // Tableau de bord
    Route::get('dashboard', DashboardController::class);
    // Compteurs de la barre (menu, alertes), relus toutes les minutes
    Route::get('overview', OverviewController::class);
    // Recherche rapide (Ctrl K) : réservations et logements
    Route::get('search', SearchController::class)->middleware('throttle:60,1');

    // Statistiques sur 12 mois glissants
    Route::get('statistics', [StatisticsController::class, 'show']);
    Route::get('statistics.csv', [StatisticsController::class, 'csv'])->middleware('throttle:10,1');

    // Planning multi-logements
    Route::get('planning', PlanningController::class);

    // Fermetures manuelles (travaux, usage personnel)
    Route::post('apartments/{apartment:id}/blocks', [BlockController::class, 'store']);
    Route::delete('blocks/{block}', [BlockController::class, 'destroy'])->whereNumber('block');

    // Messages du formulaire de contact
    Route::get('messages', [ContactMessageController::class, 'index']);
    Route::get('messages/unread-count', [ContactMessageController::class, 'unreadCount']);
    Route::patch('messages/{message}', [ContactMessageController::class, 'update'])->whereNumber('message');

    // Avis des voyageurs : modération, réponse publique, renvoi de l'invitation
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::post('reviews/{review}/publish', [ReviewController::class, 'publish'])->whereNumber('review');
    Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->whereNumber('review');
    Route::put('reviews/{review}/reply', [ReviewController::class, 'reply'])->whereNumber('review');
    Route::post('reviews/{review}/resend-invitation', [ReviewController::class, 'resendInvitation'])->whereNumber('review')->middleware('throttle:6,1');

    // Journal des actions sensibles (lecture seule)
    Route::get('audit-logs', [AuditLogController::class, 'index']);
});

/*
| Back-office : administrateurs seulement (tarifs, comptes, réglages)
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Tarifs par période
    Route::get('apartments/{apartment:id}/rate-periods', [RatePeriodController::class, 'index']);
    Route::post('apartments/{apartment:id}/rate-periods', [RatePeriodController::class, 'store']);
    Route::put('rate-periods/{ratePeriod}', [RatePeriodController::class, 'update']);
    Route::delete('rate-periods/{ratePeriod}', [RatePeriodController::class, 'destroy']);

    // Comptes du back-office : invitation du ménage, activation, lien de mot de passe
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store'])->middleware('throttle:10,1');
    Route::patch('users/{user}', [UserController::class, 'update'])->whereNumber('user');
    Route::post('users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])->whereNumber('user')->middleware('throttle:6,1');

    // Réglages de l'hôte
    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);

    // Options proposées par logement : catalogue, liste « À préparer »
    Route::get('extras', [ExtraController::class, 'index']);
    Route::post('extras', [ExtraController::class, 'store']);
    Route::put('extras/{extra}', [ExtraController::class, 'update'])->whereNumber('extra');
    Route::get('extras/to-prepare', [ExtraController::class, 'toPrepare']);

    // Codes promo et offres automatiques
    Route::get('coupons', [AdminCouponController::class, 'index']);
    Route::post('coupons', [AdminCouponController::class, 'store']);
    Route::put('coupons/{coupon}', [AdminCouponController::class, 'update'])->whereNumber('coupon');

    // Revenus Airbnb et Booking.com saisis chaque mois, par logement.
    Route::get('platform-revenues', [PlatformRevenueController::class, 'show']);
    Route::put('platform-revenues', [PlatformRevenueController::class, 'update'])->middleware('throttle:30,1');
    Route::get('platform-revenues/history', [PlatformRevenueController::class, 'history']);
});
