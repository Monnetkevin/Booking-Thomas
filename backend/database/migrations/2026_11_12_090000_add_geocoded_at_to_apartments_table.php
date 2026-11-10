<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coordonnées calculées depuis l'adresse postale. geocoded_at vide = calcul en attente ;
 * renseigné sans coordonnées = adresse introuvable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->timestamp('geocoded_at')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn('geocoded_at');
        });
    }
};
