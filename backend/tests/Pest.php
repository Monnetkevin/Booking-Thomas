<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Les tests fonctionnels tournent sur PostgreSQL (base booking_test) : la contrainte
| d'exclusion anti-chevauchement n'existe pas sous SQLite.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
