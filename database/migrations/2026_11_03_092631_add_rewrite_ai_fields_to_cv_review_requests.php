<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->string('rewrite_mode', 20)->nullable()->after('paid_for_service_key');
            // 'ai' | 'human'
            $table->json('rewrite_content')->nullable()->after('rewrite_mode');
            $table->json('rewrite_content_original')->nullable()->after('rewrite_content');
            $table->timestamp('rewrite_generated_at')->nullable()->after('rewrite_content_original');
            $table->integer('rewrite_regeneration_count')->default(0)->after('rewrite_generated_at');
            $table->integer('rewrite_max_regenerations')->default(2)->after('rewrite_regeneration_count');
            $table->boolean('rewrite_is_user_edited')->default(false)->after('rewrite_max_regenerations');
            $table->timestamp('rewrite_user_edited_at')->nullable()->after('rewrite_is_user_edited');
            $table->string('rewrite_error_message', 500)->nullable()->after('rewrite_user_edited_at');
        });
    }

    public function down(): void
    {
        Schema::table('cv_review_requests', function (Blueprint $table) {
            $table->dropColumn([
                'rewrite_mode',
                'rewrite_content',
                'rewrite_content_original',
                'rewrite_generated_at',
                'rewrite_regeneration_count',
                'rewrite_max_regenerations',
                'rewrite_is_user_edited',
                'rewrite_user_edited_at',
                'rewrite_error_message',
            ]);
        });
    }
};