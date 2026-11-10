<?php

use Illuminate\Support\Facades\Route;

// L'application est une API : les pages sont servies par la SPA React (Nginx en production, Vite en local).
Route::get('/', fn () => response()->json(['name' => config('app.name')]));
