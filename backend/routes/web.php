<?php

use App\Http\Controllers\Dev\MailPreviewController;
use App\Http\Controllers\IcalExportController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SpaPageController;
use Illuminate\Support\Facades\Route;

// Plan du site et robots.txt (Nginx les transmet à Laravel).
Route::get('sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('robots.txt', [SeoController::class, 'robots']);

// Calendrier d'un logement pour Airbnb (token secret de 40 caractères, révocable).
Route::get('ical/{token}.ics', IcalExportController::class)
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:public')
    ->name('ical.export');

// Aperçu des e-mails, en développement uniquement (jamais en recette ni en production).
if (app()->environment('local')) {
    Route::get('dev/mails', [MailPreviewController::class, 'index']);
    Route::get('dev/mails/{name}/{locale}', [MailPreviewController::class, 'show']);
    Route::get('dev/pdf/booking/{locale}', [MailPreviewController::class, 'pdf']);
}

// Pages publiques du site (Nginx confie leur premier chargement à Laravel) : index.html de la
// SPA avec les balises de la page. En local, Vite sert le site sans passer par ici.
Route::get('{path?}', SpaPageController::class)
    ->where('path', '(?!(?:api|sanctum|admin|dev|up|ical)(?:/|$)).*')
    ->middleware('throttle:public');
