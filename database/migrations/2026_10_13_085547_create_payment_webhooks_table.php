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
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('event_type')->nullable();
            $table->string('gateway_reference')->nullable()->index();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('status')->default('received');
            // received | processed | failed | ignored
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
