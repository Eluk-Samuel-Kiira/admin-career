<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_review_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services');
            $table->char('country_code', 2);                 // 'UG', 'KE', 'XX'
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('amount_cents');      // price snapshot at purchase time

            // ── Target job context ────────────────────────────────
            $table->string('target_job_title')->nullable();
            $table->text('target_job_description')->nullable();

            // ── CV files ──────────────────────────────────────────
            $table->string('original_cv_path')->nullable();
            $table->string('original_cv_name')->nullable();
            $table->string('seeker_cv_path')->nullable();

            // ── AI review + admin edits + delivery tracking ───────
            $table->json('ai_gap_review')->nullable();          // AI-generated, raw
            $table->json('admin_edited_review')->nullable();    // admin override (wins on display)
            $table->timestamp('review_delivered_at')->nullable();
            $table->string('review_delivery_channel')->nullable(); // email | whatsapp | both
            $table->json('delivery_log')->nullable();           // append-only send history

            // ── Seeker input + revisions ──────────────────────────
            $table->json('seeker_gap_answers')->nullable();
            $table->json('revision_history')->nullable();

            // ── Lifecycle ─────────────────────────────────────────
            $table->enum('status', [
                'submitted',
                'ai_reviewed',
                'awaiting_payment',
                'paid',
                'in_progress',
                'delivered',
                'revision_requested',
                'completed',
                'cancelled',
            ])->default('submitted');

            $table->string('payment_reference')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable();
            $table->string('delivered_cv_path')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['status', 'created_at']);
            $table->index(['country_code', 'status']);
            $table->index(['assigned_admin_id', 'status']);
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_review_requests');
    }
};