<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fermeture manuelle pendant laquelle le logement est occupé (famille, amis, propriétaires) :
 * le ménage est prévenu comme pour un séjour, avec un passage à la fin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->boolean('needs_cleaning')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->dropColumn('needs_cleaning');
        });
    }
};
