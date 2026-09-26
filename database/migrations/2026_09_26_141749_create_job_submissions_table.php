<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ── Ownership ────────────────────────────────────────────
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            // ── The raw submission (employer just pastes it) ─────────
            $table->string('job_title');
            $table->longText('content');             // everything else — description, requirements, how to apply
            $table->string('target_country', 2);     // which country front-end this is for

            // ── Package + payment ────────────────────────────────────
            $table->string('service_key', 100);      // job_post_free, job_post_popular, etc.
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->json('package_meta')->nullable(); // snapshot of features for this package

            $table->enum('payment_status', [
                'not_required',
                'pending',
                'paid',
                'refunded',
            ])->default('not_required');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // ── Review workflow ──────────────────────────────────────
            $table->enum('status', [
                'draft',
                'pending_payment',
                'pending_review',
                'approved',
                'rejected',
                'published',
                'cancelled',
            ])->default('draft');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            // ── Output ───────────────────────────────────────────────
            $table->foreignId('job_post_id')->nullable()->constrained('job_posts')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_submissions');
    }
};