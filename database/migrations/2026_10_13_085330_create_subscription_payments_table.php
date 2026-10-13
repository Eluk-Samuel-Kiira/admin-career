<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('gateway');                        // stripe | flutterwave | pesapal
            $table->string('gateway_reference')->nullable()->index();
            $table->string('gateway_transaction_id')->nullable()->index();

            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_cents');
            $table->string('status');                         // pending | succeeded | failed | refunded
            $table->string('failure_reason')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();      // full webhook body for auditing

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['subscription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
