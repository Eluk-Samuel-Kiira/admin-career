<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('companies');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('logo_path')->nullable(); 
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address1')->nullable();
            $table->string('company_size')->nullable();
            
            // Foreign Keys - as regular columns first (no constraints)
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            // Migration tracking
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->comment('Original ID from legacy system');
            $table->string('country_code', 2)->nullable()->default('AU')->comment('ISO 3166-1 alpha-2 country code');
            $table->string('legacy_alias')->nullable()->comment('Original alias from legacy system');
            $table->string('legacy_uid')->nullable()->comment('Original uid from legacy system');
            $table->json('legacy_metadata')->nullable()->comment('Complete legacy record for reference');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_gold')->default(false);
            $table->boolean('is_featured')->default(false);
            
            // Gold & Featured Dates
            $table->timestamp('gold_start_date')->nullable();
            $table->date('gold_end_date')->nullable();
            $table->timestamp('featured_start_date')->nullable();
            $table->date('featured_end_date')->nullable();
            
            // Package & Payment
            $table->integer('package_id')->nullable();
            $table->integer('payment_history_id')->nullable();
            $table->integer('hits')->default(0);
            
            // Migration Tracking
            $table->timestamp('migrated_at')->nullable()->comment('When this record was migrated');
            
            $table->timestamps();
            
            // Indexes
            $table->index('legacy_id');
            $table->index('country_code');
            $table->index('is_active');
            $table->index('is_verified');
            $table->index('slug');
            $table->index('industry_id');
            $table->index('location_id');
            $table->index('created_by');
            $table->index(['is_active', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};