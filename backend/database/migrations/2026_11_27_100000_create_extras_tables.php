<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Options par logement (spec « Options par logement ») : catalogue de l'hôte, logements
 * où chacune est proposée, et options figées dans chaque réservation (nom, prix, quantité)
 * pour qu'une modification ultérieure ne change pas les réservations existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            // Prix en centimes ; 0 pour une option gratuite.
            $table->unsignedInteger('price')->default(0);
            $table->string('pricing', 20);
            // Exemplaires au plus, pour une option à l'unité.
            $table->unsignedSmallInteger('max_quantity')->default(1);
            // Commande possible jusqu'à ce nombre d'heures avant l'arrivée.
            $table->unsignedSmallInteger('lead_time_hours')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('apartment_extra', function (Blueprint $table) {
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extra_id')->constrained()->cascadeOnDelete();
            $table->primary(['apartment_id', 'extra_id']);
        });

        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extra_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('pricing', 20);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_amount');
            $table->unsignedInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extras');
        Schema::dropIfExists('apartment_extra');
        Schema::dropIfExists('extras');
    }
};
