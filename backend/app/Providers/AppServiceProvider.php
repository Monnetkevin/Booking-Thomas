<?php

namespace App\Providers;

use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeoplateformeGeocoder;
use Illuminate\Http\Client\Factory as Http;
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
        //
    }
}
