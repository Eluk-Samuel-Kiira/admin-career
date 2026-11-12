<?php

// database/migrations/xxxx_add_retry_tracking_to_auto_apply_relays.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            $table->integer('send_attempts')->default(0)->after('status');
            $table->timestamp('last_attempt_at')->nullable()->after('send_attempts');
            $table->timestamp('next_retry_at')->nullable()->after('last_attempt_at');
        });
    }

    public function down(): void
    {
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            $table->dropColumn(['send_attempts', 'last_attempt_at', 'next_retry_at']);
        });
    }
};
