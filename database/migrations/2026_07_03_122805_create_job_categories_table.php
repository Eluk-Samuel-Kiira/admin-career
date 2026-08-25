<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Migration tracking
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->comment('Original ID from legacy system');
            $table->string('country_code', 2)->default('AU')->comment('ISO 3166-1 alpha-2 country code');
            $table->string('legacy_alias')->nullable()->comment('Original alias from legacy system');
            $table->string('legacy_cat_value')->nullable()->comment('Original cat_value field');
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // UI
            $table->string('icon')->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            
            // Metadata
            $table->json('legacy_metadata')->nullable()->comment('Complete legacy record for reference');
            $table->timestamp('migrated_at')->nullable()->comment('When this record was migrated');
            $table->foreignId('migrated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index('legacy_id');
            $table->index('country_code');
            $table->index('is_active');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_categories');
    }
};