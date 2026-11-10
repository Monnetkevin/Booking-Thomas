<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder unique, rejouable sans risque (y compris en production) : il crée ce qui manque
 * et ne remplace jamais ce que l'hôte a modifié. Logements, tarifs, options et coupons
 * se saisissent dans l'admin.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            AmenitySeeder::class,
        ]);
    }
}
