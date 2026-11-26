<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ical_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('airbnb');
            $table->text('import_url')->nullable(); // chiffrée (cast encrypted)
            $table->string('export_token', 64)->unique();
            $table->string('etag')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('apartment_id')->constrained()->restrictOnDelete();
            $table->string('source', 20)->default('direct');
            $table->string('status', 20)->default('pending');
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone', 40)->nullable();
            $table->char('guest_country', 2)->nullable();
            $table->text('guest_message')->nullable();
            $table->string('locale', 5)->default('fr');
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->text('housekeeping_notes')->nullable();
            // Séjour [arrival, departure[ : la nuit du départ reste libre.
            $table->date('arrival');
            $table->date('departure');
            // Montants en centimes d'euro, figés à la création.
            $table->unsignedInteger('accommodation_amount');
            $table->unsignedInteger('cleaning_fee')->default(0);
            $table->unsignedInteger('extras_amount')->default(0);
            $table->unsignedInteger('tourist_tax')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_amount');
            $table->json('price_breakdown');
            $table->json('cancellation_policy');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['apartment_id', 'arrival']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            // Plage [starts_on, ends_on[ : ends_on est le jour de départ, libre.
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('type', 20);
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('ical_feed_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('external_uid')->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('has_conflict')->default(false);
            $table->timestamps();

            $table->unique(['ical_feed_id', 'external_uid']);
            $table->index(['apartment_id', 'starts_on']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('payment');
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('availability_blocks');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('ical_feeds');
    }
};
