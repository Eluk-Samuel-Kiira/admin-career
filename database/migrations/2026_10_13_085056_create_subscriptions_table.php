<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Which service in the `services` table is this user subscribed to?
            $table->string('service_key');
            $table->foreign('service_key')->references('key')->on('services')->cascadeOnDelete();

            // The service_prices row this subscription was locked to at creation time.
            $table->foreignId('price_id')->nullable()->constrained('service_prices')->nullOnDelete();
            $table->string('country_code', 2);
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_cents');
            $table->string('interval');

            // Billing lifecycle — use datetime, not timestamp, to avoid MySQL
            // strict-mode default-value errors and the 2038 range limit.
            $table->string('status');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('current_period_start');
            $table->dateTime('current_period_end');
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            $table->json('feature_snapshot');

            $table->string('gateway')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_subscription_id')->nullable()->index();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'current_period_end']);
            $table->index(['service_key', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};