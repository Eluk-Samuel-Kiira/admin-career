<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            // The provider is not known until MarzPay responds, so this must
            // be nullable. It is filled in once the collection returns.
            $table->foreignId('payment_method_id')->nullable()->change();

            // e.g. 'cv_review', 'cover_letter', 'job_submission', 'subscription'
            $table->string('purpose_type', 64)->nullable()->after('source_id');

            // UUID of the row this deposit pays for (cv_review_requests.uuid, etc.)
            $table->uuid('purpose_reference')->nullable()->after('purpose_type');

            $table->index(['purpose_type', 'purpose_reference'], 'deposits_purpose_idx');

            // How the money moved: 'mobile_money' or 'card'
            $table->string('payment_channel', 32)->nullable()->after('deposit_method');

            // MarzPay transaction UUID (used when re-querying for verification)
            $table->string('gateway_transaction_uuid')->nullable()->after('source_reference');

            // Who this deposit is for
            $table->foreignId('paid_by_user_id')->nullable()->after('depositor_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['paid_by_user_id']);
            $table->dropColumn('paid_by_user_id');

            $table->dropColumn('gateway_transaction_uuid');
            $table->dropColumn('payment_channel');
            $table->dropIndex('deposits_purpose_idx');
            $table->dropColumn('purpose_reference');
            $table->dropColumn('purpose_type');

            // Revert payment_method_id to NOT NULL. Only safe if no nulls exist.
            $table->foreignId('payment_method_id')->nullable(false)->change();
        });
    }
};