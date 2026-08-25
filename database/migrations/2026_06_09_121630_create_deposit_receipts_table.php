<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained('deposits')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('receipt_number')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            
            $table->index('deposit_id');
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_receipts');
    }
};