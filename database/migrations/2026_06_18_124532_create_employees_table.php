<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('residence')->nullable();
            
            // Employment Information
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('job_title');
            $table->enum('employee_type', ['full_time', 'part_time', 'contract', 'intern', 'job_seeker', 'employer'])->default('full_time');
            
            // Salary Information
            $table->bigInteger('salary')->default(0);
            $table->enum('salary_type', ['fixed', 'hourly', 'commission'])->default('fixed');
            $table->boolean('is_salary_recurring')->default(true);
            $table->integer('recurring_day')->nullable();
            
            // Tax & Identification
            $table->string('nssf_number')->nullable();
            $table->string('tin_number')->nullable();
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();
            
            // Identification
            $table->enum('id_type', ['national_id', 'passport', 'driving_license', 'other'])->nullable();
            $table->string('id_number')->nullable();
            
            // Qualifications & Skills
            $table->string('qualification')->nullable();
            $table->json('skills')->nullable();
            
            // Next of Kin
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_contact')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            
            // Documents & Notes
            $table->json('documents')->nullable();
            $table->text('notes')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('department_id');
            $table->index('email');
            $table->index('employee_type');
            $table->index('is_active');
            $table->index('hire_date');
            $table->index('job_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};