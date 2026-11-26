<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réservation saisie à la main (téléphone, connaissance) : l'e-mail du voyageur peut
 * manquer. Une réservation du site en a toujours un (validé à la saisie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_email')->nullable(false)->change();
        });
    }
};
