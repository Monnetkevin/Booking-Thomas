<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications du back-office (hôte et ménage), lues dans le back-office, et préférences
 * d'envoi par e-mail de chaque compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 60);
            $table->morphs('notifiable');
            $table->string('level', 10);
            // Même type et même objet (calendrier, séjour) : la notification non lue est rafraîchie.
            $table->string('dedupe_key', 120)->nullable();
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id', 'type', 'dedupe_key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('notification_preferences')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });

        Schema::dropIfExists('notifications');
    }
};
