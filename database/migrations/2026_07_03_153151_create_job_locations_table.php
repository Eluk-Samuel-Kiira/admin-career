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
        Schema::create('job_locations', function (Blueprint $table) {
            $table->id();
            
            // Country and location details
            $table->string('country', 2)->default('UG');
            $table->string('country_code', 2)->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->default('East Africa');
            
            // Slug and SEO
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            
            // Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->nullable();
            
            // Status and sorting
            $table->boolean('is_active')->default(true);
            $table->boolean('is_capital')->default(false);
            $table->integer('sort_order')->default(0);
            
            // Foreign keys
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
            $table->index('country');
            $table->index('country_code');
            $table->index('region');
            $table->index('city');
            $table->index(['country_code', 'is_active']);
            $table->index(['latitude', 'longitude']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_locations');
    }
};