<?php

namespace Tests;

use App\Services\Geocoding\Geocoder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeGeocoder;

abstract class TestCase extends BaseTestCase
{
    protected FakeGeocoder $geocoder;

    protected function setUp(): void
    {
        parent::setUp();

        // Comme la SPA française : sans cela, le client de test envoie « en-us ».
        $this->withHeader('Accept-Language', 'fr');

        // Aucun appel réseau pendant les tests ; le géocodage est simulé.
        Http::preventStrayRequests();
        $this->geocoder = new FakeGeocoder;
        $this->app->instance(Geocoder::class, $this->geocoder);
    }
}
