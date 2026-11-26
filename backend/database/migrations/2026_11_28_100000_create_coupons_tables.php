<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes promo et offres automatiques (spec « Codes promo ») : cible, type de réduction,
 * conditions et limites ; une utilisation par réservation, libérée si elle expire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // Toujours en majuscules : la saisie est insensible à la casse.
            $table->string('code', 40)->unique();
            $table->string('target', 10);
            $table->string('type', 12);
            // Pourcentage (1 à 100) ou montant en centimes ; 0 pour une option offerte.
            $table->unsignedInteger('value')->default(0);
            $table->foreignId('extra_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('free_quantity')->default(1);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_email')->nullable();
            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->unsignedInteger('min_amount')->nullable();
            $table->boolean('automatic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('apartment_coupon', function (Blueprint $table) {
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->primary(['apartment_id', 'coupon_id']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('code', 40);
            $table->unsignedInteger('discount_amount');
            // Réservation expirée avant paiement : l'utilisation ne compte plus.
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('apartment_coupon');
        Schema::dropIfExists('coupons');
    }
};
