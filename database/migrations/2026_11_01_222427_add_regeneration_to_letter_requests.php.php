<?php
// database/migrations/xxxx_add_regeneration_and_edit_to_letter_requests.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_requests', function (Blueprint $table) {
            // Regeneration tracking
            $table->unsignedTinyInteger('regeneration_count')->default(0)->after('status');
            $table->unsignedTinyInteger('max_regenerations')->default(2)->after('regeneration_count');

            // User edits tracking
            $table->boolean('is_user_edited')->default(false)->after('generated_at');
            $table->timestamp('user_edited_at')->nullable()->after('is_user_edited');

            // Original AI content snapshot (set on first edit, cleared on reset/regenerate)
            $table->longText('content_original')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('letter_requests', function (Blueprint $table) {
            $table->dropColumn([
                'regeneration_count',
                'max_regenerations',
                'is_user_edited',
                'user_edited_at',
                'content_original',
            ]);
        });
    }
};