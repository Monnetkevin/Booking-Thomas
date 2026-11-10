<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Comme la SPA française : sans cela, le client de test envoie « en-us ».
        $this->withHeader('Accept-Language', 'fr');
    }
}
