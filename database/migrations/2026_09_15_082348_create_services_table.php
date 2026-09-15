<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // cv_review, cv_rewrite, premium_alerts
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_turnaround_hours')->default(24);
            $table->enum('billing_type', ['one_time', 'subscription', 'free']);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('billing_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};