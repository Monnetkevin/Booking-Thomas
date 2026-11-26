<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Nom libre d'une période, ex. « Semaine des 24 Heures ». */
    public function up(): void
    {
        Schema::table('rate_periods', function (Blueprint $table) {
            $table->string('label', 80)->nullable()->after('apartment_id');
        });
    }

    public function down(): void
    {
        Schema::table('rate_periods', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
