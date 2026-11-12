<?php

// database/migrations/xxxx_create_manual_email_dispatches.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_email_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('purpose');                    // product_advert, job_application_help, hiring_appointment, manual_outreach
            $table->string('audience')->default('custom'); // seekers, employers, custom
            $table->string('subject');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->json('recipients');                   // [{email, name, user_id?}]
            $table->integer('recipient_count')->default(0);
            $table->string('status')->default('queued');  // queued, sending, sent, partial, failed
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('employer_email')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('seeker_name')->nullable();
            $table->string('seeker_email')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('deposit_id')->nullable()->constrained('deposits')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['purpose', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_email_dispatches');
    }
};
