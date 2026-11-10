<?php

use App\Http\Controllers\Admin\ApartmentController;
use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

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
});
