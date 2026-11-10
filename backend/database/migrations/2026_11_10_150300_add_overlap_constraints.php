<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La base elle-même refuse les chevauchements :
 * - deux blocs de disponibilité actifs (hors conflits iCal signalés) d'un même logement ;
 * - deux périodes tarifaires d'un même logement.
 * Une violation lève SQLSTATE 23P01 (exclusion_violation).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement('ALTER TABLE availability_blocks
            ADD CONSTRAINT availability_blocks_valid_range CHECK (ends_on > starts_on)');

        // [starts_on, ends_on[ : le jour de départ reste libre pour une nouvelle arrivée.
        DB::statement("ALTER TABLE availability_blocks
            ADD CONSTRAINT availability_blocks_no_overlap
            EXCLUDE USING gist (
                apartment_id WITH =,
                daterange(starts_on, ends_on, '[)') WITH &&
            ) WHERE (is_active AND NOT has_conflict)");

        DB::statement('ALTER TABLE bookings
            ADD CONSTRAINT bookings_valid_stay CHECK (departure > arrival)');

        DB::statement('ALTER TABLE rate_periods
            ADD CONSTRAINT rate_periods_valid_range CHECK (ends_on >= starts_on)');

        // Périodes inclusives : [starts_on, ends_on].
        DB::statement("ALTER TABLE rate_periods
            ADD CONSTRAINT rate_periods_no_overlap
            EXCLUDE USING gist (
                apartment_id WITH =,
                daterange(starts_on, ends_on, '[]') WITH &&
            )");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE rate_periods DROP CONSTRAINT IF EXISTS rate_periods_no_overlap');
        DB::statement('ALTER TABLE rate_periods DROP CONSTRAINT IF EXISTS rate_periods_valid_range');
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_valid_stay');
        DB::statement('ALTER TABLE availability_blocks DROP CONSTRAINT IF EXISTS availability_blocks_no_overlap');
        DB::statement('ALTER TABLE availability_blocks DROP CONSTRAINT IF EXISTS availability_blocks_valid_range');
    }
};
