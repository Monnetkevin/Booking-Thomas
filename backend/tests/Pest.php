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

/** Corps valide pour POST/PUT /api/admin/apartments. */
function apartmentPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
        'description' => ['fr' => 'Bohème, rotin et lumière.', 'en' => ''],
        'address' => '12 rue de la Paix',
        'postal_code' => '72000',
        'city' => 'Le Mans',
        'capacity' => 4,
        'bedrooms' => 1,
        'beds' => 2,
        'bathrooms' => 1,
        'surface_m2' => 60,
        'check_in_time' => '16:00',
        'check_out_time' => '10:00',
        'min_stay' => 2,
        'max_stay' => 28,
        'turnover_days' => 0,
        'cleaning_fee' => 4500,
        'cancellation_policy' => 'moderate',
    ], $overrides);
}
