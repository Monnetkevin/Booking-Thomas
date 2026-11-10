<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// L'application est une API : les pages sont servies par la SPA React (Nginx en production, Vite en local).
Route::get('/', fn () => response()->json(['name' => config('app.name')]));

// Plan du site et robots.txt (Nginx les transmet à Laravel).
Route::get('sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('robots.txt', [SeoController::class, 'robots']);
