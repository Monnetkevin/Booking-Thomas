<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revenus des plateformes : l'import des fichiers CSV (une ligne par versement) est abandonné
 * au profit d'une saisie à la main du net versé, par plateforme, logement et mois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_monthly_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            // Premier jour du mois.
            $table->date('month');
            // Net versé à l'hôte, en centimes (0 : aucun revenu ce mois-là).
            $table->unsignedInteger('net_amount');
            // Auteur de la dernière saisie.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['platform', 'apartment_id', 'month']);
            $table->index('month');
        });

        // Tables de l'import CSV abandonné (migration 2026_11_20_170000).
        Schema::dropIfExists('platform_payouts');
        Schema::dropIfExists('platform_listings');
        Schema::dropIfExists('platform_imports');
        // Le journal garde les imports passés (AuditAction::PlatformPayoutsImported).
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_monthly_revenues');
        // Les tables de l'import CSV (abandonné) ne sont pas recréées.
    }
};
