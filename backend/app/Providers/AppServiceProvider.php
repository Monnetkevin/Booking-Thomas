<?php

namespace App\Providers;

use App\Services\Geocoding\FallbackGeocoder;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeoplateformeGeocoder;
use App\Services\Geocoding\NominatimGeocoder;
use App\Services\Security\HumanVerifier;
use App\Services\Security\TurnstileVerifier;
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
        // IGN en priorité (adresses françaises), OpenStreetMap si l'IGN est injoignable.
        $this->app->bind(Geocoder::class, fn ($app) => new FallbackGeocoder(
            new GeoplateformeGeocoder($app->make(Http::class), (string) config('services.geocoding.url')),
            new NominatimGeocoder(
                $app->make(Http::class),
                (string) config('services.geocoding.fallback_url'),
                (string) config('services.geocoding.user_agent'),
            ),
        ));
        $this->app->bind(HumanVerifier::class, fn ($app) => new TurnstileVerifier(
            $app->make(Http::class),
            config('services.turnstile.secret'),
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
        // Contact : 3 messages par minute et par IP (spec).
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by((string) $request->ip()));
    }
}
