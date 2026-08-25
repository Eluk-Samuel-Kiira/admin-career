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
        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Country
            $table->string('country_code', 2)->default('UG');
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Foreign keys
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('country_code');
            $table->index(['is_active', 'sort_order']);
            $table->index(['country_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('education_levels');
    }
};