<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_screening_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('job_post_id')->constrained('job_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('failed')->default(0);

            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])
                ->default('queued');

            $table->json('applicant_ids')->nullable();
            $table->json('failed_ids')->nullable();
            $table->string('job_fingerprint', 64)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['job_post_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_screening_batches');
    }
};