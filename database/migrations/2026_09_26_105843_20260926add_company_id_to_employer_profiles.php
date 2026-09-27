<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employer_profiles', 'company_id')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('companies')
                    ->nullOnDelete();

                $table->index('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employer_profiles', 'company_id')) {
            Schema::table('employer_profiles', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};