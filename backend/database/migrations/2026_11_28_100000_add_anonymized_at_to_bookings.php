<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Réservation dont le voyageur a été anonymisé (RGPD) : coordonnées effacées, montants gardés.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable();
            $table->index(['anonymized_at', 'departure']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['anonymized_at', 'departure']);
            $table->dropColumn('anonymized_at');
        });
    }
};
