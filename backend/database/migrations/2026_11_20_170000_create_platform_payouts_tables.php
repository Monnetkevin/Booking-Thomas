<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revenus des plateformes (Airbnb, Booking.com) importés à la main depuis leurs exports CSV :
 * l'iCal ne transmet aucun montant. Une ligne par réservation de la plateforme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_imports', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->string('filename', 190);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('rows_created')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->timestamps();
        });

        // Annonce de la plateforme → logement : retenu d'un import à l'autre.
        Schema::create('platform_listings', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->string('listing', 190);
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['platform', 'listing']);
        });

        Schema::create('platform_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            // Code de confirmation de la plateforme : clé du ré-import sans doublon.
            $table->string('confirmation_code', 64);
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('arrival');
            $table->date('departure');
            $table->unsignedSmallInteger('nights');
            // Montants en centimes : brut payé par le voyageur, frais de la plateforme, net versé à l'hôte.
            $table->integer('gross_amount')->nullable();
            $table->integer('fees')->nullable();
            $table->integer('net_amount');
            $table->timestamps();
            $table->unique(['platform', 'confirmation_code']);
            $table->index(['arrival']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payouts');
        Schema::dropIfExists('platform_listings');
        Schema::dropIfExists('platform_imports');
    }
};
