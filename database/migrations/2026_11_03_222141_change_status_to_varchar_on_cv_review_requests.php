<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run if the column is currently ENUM.
        // If it's already VARCHAR, this migration is a no-op — safe to re-run
        // after a db reset with different history.
        $col = DB::selectOne("SHOW COLUMNS FROM `cv_review_requests` WHERE Field = 'status'");

        if ($col && stripos($col->Type, 'enum') === 0) {
            DB::statement("
                ALTER TABLE `cv_review_requests`
                MODIFY COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'submitted'
            ");
        }
    }

    public function down(): void
    {
        // One-way migration. Reverting requires all rows to have a status
        // that existed in the original ENUM, so we intentionally do nothing
        // here. If you truly need to roll back, do it manually.
    }
};