<?php

namespace App\Console\Commands;

use App\Jobs\RunAutoApplyForSeekerJob;
use App\Models\SeekerProfile;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Console\Command;

class RunAutoApplyCommand extends Command
{
    protected $signature   = 'auto-apply:run {--dry-run} {--user=} {--chunk=500}';
    protected $description = 'Dispatch the daily auto-apply runs for all eligible subscribers.';

    public function handle(SubscriptionGate $gate): int
    {
        $chunkSize = (int) $this->option('chunk');
        $query = SeekerProfile::query()->where('is_active', true);

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $total = 0;
        $query->chunkById($chunkSize, function ($seekers) use (&$total, $gate) {
            foreach ($seekers as $seeker) {
                if (!$seeker->user || !$seeker->user->is_active) continue;
                if (!$gate->hasAutoApply($seeker->user)) continue;

                if ($this->option('dry-run')) {
                    $this->line("would dispatch: seeker {$seeker->id} ({$seeker->user->email})");
                    $total++;
                    continue;
                }

                RunAutoApplyForSeekerJob::dispatch($seeker->id)
                    ->onQueue(config('auto_apply.queue', 'auto-apply'));
                $total++;
            }
        });

        $this->info("Dispatched {$total} auto-apply runs.");
        return self::SUCCESS;
    }
}