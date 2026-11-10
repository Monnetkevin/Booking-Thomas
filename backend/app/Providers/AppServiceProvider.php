<?php

namespace App\Providers;

use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeoplateformeGeocoder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Geocoder::class, fn ($app) => new GeoplateformeGeocoder(
            $app->make(Http::class),
            (string) config('services.geocoding.url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lecture publique : 120 requêtes par minute et par adresse IP.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by((string) $request->ip()));
        // Devis : 30 par minute et par IP (spec).
        RateLimiter::for('quotes', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
    }
}
