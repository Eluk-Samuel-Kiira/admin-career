<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->char('country_code', 2); // ISO 3166-1 alpha-2, or 'XX' for global fallback
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('amount_cents');
            $table->enum('interval', ['month', 'year'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One price per service per country
            $table->unique(['service_id', 'country_code']);

            $table->index(['service_id', 'is_active']);
            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};