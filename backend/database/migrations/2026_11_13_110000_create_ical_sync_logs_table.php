<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des synchronisations : date du dernier import réussi et journal de chaque
 * lecture d'un calendrier (conservé 30 jours).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->timestamp('last_succeeded_at')->nullable()->after('last_synced_at');
        });

        Schema::create('ical_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ical_feed_id')->constrained()->cascadeOnDelete();
            $table->string('trigger', 20);
            $table->string('status', 20);
            $table->unsignedSmallInteger('created_count')->default(0);
            $table->unsignedSmallInteger('updated_count')->default(0);
            $table->unsignedSmallInteger('removed_count')->default(0);
            $table->unsignedSmallInteger('conflict_count')->default(0);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ical_feed_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ical_sync_logs');

        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->dropColumn('last_succeeded_at');
        });
    }
};
