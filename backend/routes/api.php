<?php

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
    //
});
