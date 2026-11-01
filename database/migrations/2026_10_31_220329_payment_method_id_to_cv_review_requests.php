<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            // Which wallet received the payment. Nullable because the request
            // exists in a pending state before any money is received.
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('currency_id')
                ->constrained('payment_methods')
                ->nullOnDelete();

            // Records which service was actually paid for. The request row always
            // starts as 'cv_review' (free), but when the seeker reaches the pay
            // step they are buying 'cv_rewrite'. This column stores that.
            $table->string('paid_for_service_key', 64)
                ->nullable()
                ->after('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
            $table->dropColumn('paid_for_service_key');
        });
    }
};