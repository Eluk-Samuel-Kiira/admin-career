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
        // database/migrations/xxxx_add_payment_method_id_to_cv_review_requests.php
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('currency_id')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            //
        });
    }
};
