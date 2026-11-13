<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import des calendriers Airbnb :
 * - lien vers la réservation chez le fournisseur, quand son flux le donne ;
 * - un seul flux par logement et par fournisseur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->string('external_url', 500)->nullable()->after('external_uid');
        });

        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->unique(['apartment_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->dropUnique(['apartment_id', 'provider']);
        });

        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->dropColumn('external_url');
        });
    }
};
