<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_profit_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            
            $table->string('financial_year');
            $table->bigInteger('total_profit');
            $table->decimal('profit_share_percentage', 5, 2);
            $table->bigInteger('profit_share_amount');
            $table->integer('total_units');
            $table->bigInteger('unit_value');
            
            $table->date('distribution_date')->nullable();
            $table->enum('status', ['pending', 'calculated', 'distributed', 'closed'])->default('pending');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('department_id');
            $table->index('financial_year');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_profit_shares');
    }
};