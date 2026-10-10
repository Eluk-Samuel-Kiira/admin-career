<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hiring_run_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_run_id')->constrained()->cascadeOnDelete();

            $table->string('original_name');            // file name
            $table->string('stored_path');              // where the CV is stored
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime_type')->nullable();

            $table->string('candidate_name')->nullable();   // extracted from CV
            $table->string('candidate_email')->nullable();
            $table->string('candidate_phone')->nullable();

            $table->unsignedTinyInteger('score')->nullable();    // 0-100
            $table->string('recommendation')->nullable();        // strong_yes | maybe | no
            $table->text('summary')->nullable();
            $table->json('strengths')->nullable();
            $table->json('gaps')->nullable();
            $table->json('red_flags')->nullable();
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();

            $table->unsignedInteger('years_of_experience')->nullable();
            $table->string('highest_education')->nullable();
            $table->string('current_title')->nullable();

            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('screened_at')->nullable();

            $table->timestamps();
            $table->index(['hiring_run_id', 'status']);
            $table->index(['hiring_run_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hiring_run_candidates');
    }
};
