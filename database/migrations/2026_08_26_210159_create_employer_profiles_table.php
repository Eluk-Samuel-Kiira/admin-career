<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Company details
            $table->string('company_name');
            $table->string('company_logo')->nullable();
            $table->string('company_website')->nullable();
            $table->text('company_description')->nullable();
            $table->string('company_size')->nullable();
            
            // Contact details
            $table->string('contact_name')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            
            // Location
            $table->string('industry')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->string('postal_code')->nullable();
            
            // Status
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('verification_status')->default('pending');
            
            // Subscription
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->integer('job_postings_remaining')->nullable();
            $table->timestamp('featured_until')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('country_code');
            $table->index('is_verified');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employer_profiles');
    }
};