<?php

namespace App\Console\Commands;

use App\Models\Service\LetterRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedLetterCvs extends Command
{
    protected $signature = 'letters:cleanup-temp-cvs
                            {--hours=24 : Delete temp CVs older than this many hours}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete orphaned temp CVs left behind by generated/failed letters';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dry   = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        // Files that still belong to a letter that MIGHT need them:
        //   - status = pending_payment  (user hasn't paid yet)
        //   - status = paid/processing  (job in flight)
        //   - status = failed with budget left  (user can still retry)
        //
        // Everything else (generated, failed+exhausted, or missing letter)
        // is safe to delete after $hours.

        $keepPaths = LetterRequest::query()
            ->where(function ($q) {
                $q->whereIn('status', ['pending_payment', 'paid', 'processing'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'failed')
                         ->whereColumn('regeneration_count', '<', 'max_regenerations');
                  });
            })
            ->whereNotNull('cv_path')
            ->pluck('cv_path')
            ->all();

        $keep = array_flip($keepPaths);

        $disk = Storage::disk('public');
        $all  = $disk->files('tmp/letters');
        $deleted = 0;
        $kept = 0;
        $freed = 0;

        foreach ($all as $path) {
            if (isset($keep[$path])) {
                $kept++;
                continue;
            }

            $mtime = $disk->lastModified($path);
            if ($mtime > $cutoff->timestamp) {
                $kept++;
                continue;
            }

            $size = $disk->size($path);

            if ($dry) {
                $this->line("[dry-run] would delete {$path} ({$size} bytes)");
                $freed += $size;
                $deleted++;
                continue;
            }

            try {
                $disk->delete($path);
                $deleted++;
                $freed += $size;
            } catch (\Throwable $e) {
                Log::warning('Failed to delete temp CV', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf(
            '%s%d file(s) deleted, %d kept, %.2f MB %s.',
            $dry ? '[dry-run] ' : '',
            $deleted,
            $kept,
            $freed / 1024 / 1024,
            $dry ? 'would be freed' : 'freed'
        ));

        if (!$dry) {
            Log::info('Letter temp CV cleanup complete', [
                'deleted' => $deleted,
                'kept'    => $kept,
                'freed_mb'=> round($freed / 1024 / 1024, 2),
            ]);
        }

        return self::SUCCESS;
    }
}