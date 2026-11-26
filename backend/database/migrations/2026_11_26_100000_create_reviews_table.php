<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis des voyageurs (spec « Avis voyageurs ») : une ligne par réservation du site,
 * créée à l'envoi de l'invitation (jeton haché, valable 30 jours), remplie par le
 * voyageur, puis publiée ou refusée par l'hôte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained();
            $table->string('status', 16)->default('invited');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('invited_at');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->string('display_name', 50)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            // Motif interne d'un refus (communicable au voyageur sur demande, jamais publié).
            $table->text('rejection_reason')->nullable();
            $table->text('host_reply')->nullable();
            $table->timestamps();

            $table->index(['status', 'apartment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
