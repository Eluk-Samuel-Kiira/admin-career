<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->foreignId('created_by_admin_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('users')
                  ->nullOnDelete();

            $table->unsignedBigInteger('job_post_id')
                  ->nullable()
                  ->after('target_job_title');

            $table->string('target_company_name', 255)
                  ->nullable()
                  ->after('target_job_title');

            $table->uuid('cover_letter_uuid')
                  ->nullable()
                  ->after('rewrite_error_message');

            $table->boolean('covered_by_subscription')
                  ->default(false)
                  ->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->dropForeign(['created_by_admin_id']);
            $table->dropColumn([
                'created_by_admin_id',
                'job_post_id',
                'target_company_name',
                'cover_letter_uuid',
                'covered_by_subscription',
            ]);
        });
    }
};