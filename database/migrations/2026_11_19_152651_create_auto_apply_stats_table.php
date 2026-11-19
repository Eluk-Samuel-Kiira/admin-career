<?php
// database/migrations/2026_11_19_000000_setup_auto_apply_stats_and_cv_delivery.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════
        // 1. auto_apply_stats — subscriber counters
        // ══════════════════════════════════════════════════════════════
        if (!Schema::hasTable('auto_apply_stats')) {
            Schema::create('auto_apply_stats', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->unique()
                    ->constrained()
                    ->cascadeOnDelete();

                // Application counters (auto-apply agent, jobs submitted)
                $table->unsignedInteger('applied_total')->default(0);
                $table->unsignedInteger('applied_via_email')->default(0);
                $table->unsignedInteger('applied_via_whatsapp')->default(0);
                $table->unsignedInteger('applied_via_external')->default(0);
                $table->timestamp('last_applied_at')->nullable();

                // CV delivery counters (paid CV rewrite service)
                $table->unsignedInteger('cv_reviews_delivered')->default(0);
                $table->unsignedInteger('cv_delivered_via_email')->default(0);
                $table->unsignedInteger('cv_delivered_via_whatsapp')->default(0);
                $table->unsignedInteger('cv_delivered_via_external')->default(0);
                $table->timestamp('last_cv_delivered_at')->nullable();

                $table->timestamps();

                $table->index('last_applied_at');
                $table->index('last_cv_delivered_at');
            });
        } else {
            // Table exists but might be missing columns from a partial run —
            // add them safely one by one.
            Schema::table('auto_apply_stats', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_apply_stats', 'cv_reviews_delivered')) {
                    $table->unsignedInteger('cv_reviews_delivered')->default(0);
                }
                if (!Schema::hasColumn('auto_apply_stats', 'cv_delivered_via_email')) {
                    $table->unsignedInteger('cv_delivered_via_email')->default(0);
                }
                if (!Schema::hasColumn('auto_apply_stats', 'cv_delivered_via_whatsapp')) {
                    $table->unsignedInteger('cv_delivered_via_whatsapp')->default(0);
                }
                if (!Schema::hasColumn('auto_apply_stats', 'cv_delivered_via_external')) {
                    $table->unsignedInteger('cv_delivered_via_external')->default(0);
                }
                if (!Schema::hasColumn('auto_apply_stats', 'last_cv_delivered_at')) {
                    $table->timestamp('last_cv_delivered_at')->nullable();
                }
            });
        }

        // ══════════════════════════════════════════════════════════════
        // 2. cv_review_requests — delivery_counted_at
        // ══════════════════════════════════════════════════════════════
        if (!Schema::hasColumn('cv_review_requests', 'delivery_counted_at')) {
            Schema::table('cv_review_requests', function (Blueprint $table) {
                // No ->after() to avoid depending on delivered_at existing.
                $table->timestamp('delivery_counted_at')->nullable();
            });
        }

        Schema::table('auto_apply_stats', function (Blueprint $table) {
            if (!Schema::hasColumn('auto_apply_stats', 'cv_delivered_via_manual')) {
                $table->unsignedInteger('cv_delivered_via_manual')->default(0)->after('cv_delivered_via_external');
            }
        });

    }

    public function down(): void
    {
        if (Schema::hasColumn('cv_review_requests', 'delivery_counted_at')) {
            Schema::table('cv_review_requests', function (Blueprint $table) {
                $table->dropColumn('delivery_counted_at');
            });
        }

        Schema::dropIfExists('auto_apply_stats');
    }
};