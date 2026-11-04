<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCvRewriteJob;
use App\Jobs\GenerateLetterJob;
use App\Models\Service\CvReviewRequest;
use App\Models\Service\LetterRequest;
use Illuminate\Console\Command;

class RedispatchStuckJobs extends Command
{
    protected $signature = 'queue:redispatch-stuck
                            {--minutes=15 : Age threshold in minutes}
                            {--dry-run : Show what would be re-dispatched without dispatching}';

    protected $description = 'Re-dispatch paid letters/CVs that never got a generation job';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));
        $dry    = (bool) $this->option('dry-run');

        $total = 0;

        // Letters stuck at 'paid' for >15 min with no content and no error
        $letters = LetterRequest::where('status', 'paid')
            ->whereNull('generated_at')
            ->whereNull('error_message')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($letters as $l) {
            if ($dry) {
                $this->line("[dry] would re-dispatch letter #{$l->id} ({$l->uuid})");
                $total++;
                continue;
            }
            GenerateLetterJob::dispatch($l->id)->onQueue('letters');
            $this->line("Re-dispatched letter #{$l->id} ({$l->uuid})");
            $total++;
        }

        // CV rewrites stuck at 'paid_ai'
        $cvs = CvReviewRequest::where('status', 'paid_ai')
            ->whereNull('rewrite_generated_at')
            ->whereNull('rewrite_error_message')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($cvs as $c) {
            if ($dry) {
                $this->line("[dry] would re-dispatch CV #{$c->id} ({$c->uuid})");
                $total++;
                continue;
            }
            GenerateCvRewriteJob::dispatch($c->id)->onQueue('letters');
            $this->line("Re-dispatched CV #{$c->id} ({$c->uuid})");
            $total++;
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Done. Re-dispatched {$total} job(s).");

        return self::SUCCESS;
    }
}