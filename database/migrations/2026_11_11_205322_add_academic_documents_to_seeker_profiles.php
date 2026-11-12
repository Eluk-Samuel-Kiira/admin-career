<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            $table->string('academic_documents_path')->nullable()->after('cv_files');
            $table->string('academic_documents_original_name')->nullable()->after('academic_documents_path');
            $table->unsignedBigInteger('academic_documents_size')->nullable()->after('academic_documents_original_name');
            $table->timestamp('academic_documents_uploaded_at')->nullable()->after('academic_documents_size');
        });
    }

    public function down(): void
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'academic_documents_path',
                'academic_documents_original_name',
                'academic_documents_size',
                'academic_documents_uploaded_at',
            ]);
        });
    }
};
