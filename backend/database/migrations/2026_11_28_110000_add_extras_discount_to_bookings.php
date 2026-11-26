<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part de la réduction (discount_amount) qui porte sur les options : nécessaire au
 * remboursement et au chiffre d'affaires des options. 0 pour une réservation manuelle,
 * dont la remise sur les options réduit directement extras_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('extras_discount_amount')->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('extras_discount_amount');
        });
    }
};
