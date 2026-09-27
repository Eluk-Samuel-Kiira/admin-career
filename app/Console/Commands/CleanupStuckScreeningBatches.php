<?php

namespace App\Console\Commands;

use App\Models\Job\JobScreeningBatch;
use Illuminate\Console\Command;

class CleanupStuckScreeningBatches extends Command
{
    protected $signature = 'screening:cleanup-stuck';
    protected $description = 'Mark screening batches stuck in processing as failed';

    public function handle(): int
    {
        $stuck = JobScreeningBatch::where('status', 'processing')
            ->where('updated_at', '<', now()->subHours(2))
            ->get();

        foreach ($stuck as $batch) {
            $batch->update([
                'status'       => 'failed',
                'completed_at' => now(),
            ]);
            $this->warn("Marked batch {$batch->uuid} as failed (stuck for >2h).");
        }

        $this->info("Cleaned up {$stuck->count()} stuck batch(es).");

        return self::SUCCESS;
    }
}