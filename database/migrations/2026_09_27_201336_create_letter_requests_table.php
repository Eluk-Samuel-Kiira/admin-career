<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seeker_profile_id')->nullable()->constrained('seeker_profiles')->nullOnDelete();

            // CV source
            $table->enum('cv_source', ['existing', 'uploaded'])->default('existing');
            $table->string('cv_path')->nullable();       // path on public disk (existing) or tmp path (uploaded)

            // Target job
            $table->foreignId('job_post_id')->nullable()->constrained('job_posts')->nullOnDelete();
            $table->string('job_title');
            $table->string('company_name');
            $table->text('job_description')->nullable(); // only when pasted

            // Letter
            $table->enum('letter_type', ['cover', 'application'])->default('cover');
            $table->longText('content')->nullable();

            // Payment
            $table->decimal('price', 10, 2)->default(3000);
            $table->char('currency', 3)->default('UGX');
            $table->enum('status', [
                'pending_payment',
                'paid',
                'processing',
                'generated',
                'failed',
            ])->default('pending_payment');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Generation
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
    }
};