<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fermeture importée posée sur des nuits déjà fermées (même date fermée sur le site et sur
 * Booking.com) : le bloc reste hors contrainte d'exclusion (has_conflict), mais ce n'est
 * pas un conflit à montrer ni à signaler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->boolean('is_redundant_closure')->default(false)->after('has_conflict');
        });
    }

    public function down(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->dropColumn('is_redundant_closure');
        });
    }
};
