<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anomalies d'import à montrer à l'hôte :
 * - calendrier vide refusé, que l'hôte peut confirmer pour libérer les nuits ;
 * - événements présents dans le flux mais aux dates illisibles, comptés à chaque lecture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->boolean('empty_calendar_refused')->default(false)->after('consecutive_failures');
        });

        Schema::table('ical_sync_logs', function (Blueprint $table) {
            // Pas de smallint : un flux de 5 Mo peut contenir plus de 32 767 événements illisibles.
            $table->unsignedInteger('unreadable_count')->default(0)->after('conflict_count');
        });
    }

    public function down(): void
    {
        Schema::table('ical_sync_logs', function (Blueprint $table) {
            $table->dropColumn('unreadable_count');
        });

        Schema::table('ical_feeds', function (Blueprint $table) {
            $table->dropColumn('empty_calendar_refused');
        });
    }
};
