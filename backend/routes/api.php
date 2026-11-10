<?php

use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\ApartmentController;
use App\Http\Controllers\Admin\ApartmentPhotoController;
use App\Http\Controllers\Admin\RatePeriodController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Public\ApartmentController as PublicApartmentController;
use Illuminate\Support\Facades\Route;

/*
| Site public : lecture seule, réponses en cache (Redis et en-têtes HTTP, une minute)
*/
Route::middleware(['throttle:public', 'cache.headers:public;max_age=60;etag'])->group(function () {
    Route::get('apartments', [PublicApartmentController::class, 'index']);
    Route::get('apartments/{slug}', [PublicApartmentController::class, 'show'])->where('slug', '[a-z0-9-]+');
});

/*
| Authentification (SPA, cookie de session Sanctum)
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [SessionController::class, 'store'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [SessionController::class, 'show'])->middleware('role:admin,housekeeping');
        Route::post('logout', [SessionController::class, 'destroy']);
    });
});

/*
| Back-office : réservé aux administrateurs (routes ajoutées par les jalons suivants)
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

    // Tarifs par période
    Route::get('apartments/{apartment:id}/rate-periods', [RatePeriodController::class, 'index']);
    Route::post('apartments/{apartment:id}/rate-periods', [RatePeriodController::class, 'store']);
    Route::put('rate-periods/{ratePeriod}', [RatePeriodController::class, 'update']);
    Route::delete('rate-periods/{ratePeriod}', [RatePeriodController::class, 'destroy']);
});
