<?php

use App\Http\Controllers\Dev\MailPreviewController;
use App\Http\Controllers\IcalExportController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// L'application est une API : les pages sont servies par la SPA React (Nginx en production, Vite en local).
Route::get('/', fn () => response()->json(['name' => config('app.name')]));

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
