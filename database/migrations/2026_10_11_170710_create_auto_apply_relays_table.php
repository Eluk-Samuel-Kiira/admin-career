<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_apply_relays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_apply_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained('job_posts')->cascadeOnDelete();

            $table->string('reason');                   // no_direct_channel | academic_documents_required
            $table->json('channels_attempted');         // ['email','whatsapp','telegram']
            $table->json('channels_sent');              // ['email']

            $table->string('cv_path')->nullable();      // tailored CV on public disk
            $table->text('cover_letter')->nullable();   // HTML

            $table->string('status')->default('queued');
            // queued | sent | partial | failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['auto_apply_run_id', 'job_post_id'], 'auto_apply_relays_run_job_unique');
            $table->index(['user_id', 'status'], 'auto_apply_relays_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_apply_relays');
    }
};