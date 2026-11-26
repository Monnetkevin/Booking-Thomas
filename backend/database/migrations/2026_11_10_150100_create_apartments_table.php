<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('address');
            $table->string('postal_code', 10);
            $table->string('city');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('beds')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->unsignedSmallInteger('surface_m2')->nullable();
            $table->time('check_in_time')->default('16:00');
            $table->time('check_out_time')->default('10:00');
            $table->unsignedSmallInteger('min_stay')->default(2);
            $table->unsignedSmallInteger('max_stay')->default(28);
            $table->unsignedSmallInteger('turnover_days')->default(0);
            $table->unsignedInteger('cleaning_fee')->default(0);
            $table->string('cancellation_policy', 20)->default('moderate');
            $table->string('registration_number')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['status', 'position']);
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('label');
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('amenity_apartment', function (Blueprint $table) {
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['apartment_id', 'amenity_id']);
        });

        Schema::create('rate_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            // Période inclusive : du starts_on au ends_on compris (nuits).
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('nightly_price');
            $table->unsignedSmallInteger('min_stay')->nullable();
            $table->unsignedInteger('weekend_supplement')->nullable();
            $table->json('arrival_days')->nullable();
            $table->timestamps();

            $table->index(['apartment_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_periods');
        Schema::dropIfExists('amenity_apartment');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('apartments');
    }
};
