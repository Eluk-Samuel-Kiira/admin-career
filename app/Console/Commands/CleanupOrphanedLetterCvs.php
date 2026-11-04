<?php

namespace App\Console\Commands;

use App\Models\Service\LetterRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedLetterCvs extends Command
{
    protected $signature = 'letters:cleanup-temp-cvs
                            {--hours=24 : Delete temp CVs older than this many hours (default for orphaned files)}
                            {--max-age-days=3 : Hard cap — delete any uploaded CV older than this, regardless of status}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete orphaned temp CVs and CVs stuck in-flight for more than N days';

    public function handle(): int
    {
        $hours      = (int) $this->option('hours');
        $maxAgeDays = (int) $this->option('max-age-days');
        $dry        = (bool) $this->option('dry-run');

        $orphanCutoff = now()->subHours($hours);         // for files NOT referenced by any letter
        $hardCutoff   = now()->subDays($maxAgeDays);     // for files referenced but too old

        // ── Build the "keep" list ──────────────────────────────────
        // A file is KEPT if its letter is:
        //   - in-flight (paid/processing) — job might be running
        //   - pending_payment AND created within the hard cutoff
        //   - failed with retries left AND created within the hard cutoff
        //
        // A file is DELETED if:
        //   - no letter references it (orphan) AND file mtime > $orphanCutoff
        //   - referenced by a letter that's older than $hardCutoff (3 days by default)
        //     regardless of status — kills pending_payment zombies and stuck paid
        //   - referenced by a letter in generated / completed / cancelled

        $keepPaths = LetterRequest::query()
            ->where('created_at', '>=', $hardCutoff)   // ← only recent letters can keep files
            ->where(function ($q) {
                $q->whereIn('status', ['paid', 'processing'])   // actively running
                  ->orWhere(function ($q2) {
                      // pending_payment — recent, user might pay
                      $q2->where('status', 'pending_payment');
                  })
                  ->orWhere(function ($q3) {
                      // failed — recent, retries remain
                      $q3->where('status', 'failed')
                         ->whereColumn('regeneration_count', '<', 'max_regenerations');
                  });
            })
            ->whereNotNull('cv_path')
            ->pluck('cv_path')
            ->all();

        $keep = array_flip($keepPaths);

        // ── Sweep the tmp folder ───────────────────────────────────
        $disk    = Storage::disk('public');
        $all     = $disk->files('tmp/letters');
        $deleted = 0;
        $kept    = 0;
        $freed   = 0;
        $reasons = ['orphan' => 0, 'expired' => 0];

        foreach ($all as $path) {
            if (isset($keep[$path])) {
                $kept++;
                continue;
            }

            $mtime = $disk->lastModified($path);

            // Determine why we're deleting (for the log)
            $reason = 'orphan';
            if ($mtime > $orphanCutoff->timestamp && !isset($keep[$path])) {
                // File exists, no current letter wants it — treat as orphan
                // but respect the "orphan" cutoff to avoid racing with a
                // letter created in the last few hours
                if ($mtime > $orphanCutoff->timestamp) {
                    $kept++;
                    continue;
                }
            }

            $size = $disk->size($path);

            if ($dry) {
                $this->line("[dry-run] would delete {$path} ({$size} bytes, mtime=" . date('Y-m-d H:i', $mtime) . ")");
                $freed += $size;
                $deleted++;
                $reasons[$reason]++;
                continue;
            }

            try {
                $disk->delete($path);
                $deleted++;
                $freed += $size;
                $reasons[$reason]++;
            } catch (\Throwable $e) {
                Log::warning('Failed to delete temp CV', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── Second sweep: stale letters whose file was deleted ─────
        // If the file is gone but the letter is still pending_payment or
        // failed, mark it so the UI shows a clear error instead of a
        // spinning "generating" that will never complete.
        $updatedLetters = 0;
        if (!$dry) {
            $staleLetters = LetterRequest::query()
                ->where('created_at', '<', $hardCutoff)
                ->whereIn('status', ['pending_payment', 'paid', 'processing'])
                ->whereNotNull('cv_path')
                ->get();

            foreach ($staleLetters as $letter) {
                if (!Storage::disk('public')->exists($letter->cv_path)) {
                    $letter->update([
                        'status'        => 'failed',
                        'error_message' => "Your uploaded CV expired after {$maxAgeDays} days and was deleted. "
                                         . "Please upload it again to regenerate.",
                    ]);
                    $updatedLetters++;
                    Log::info('Marked expired letter as failed', [
                        'uuid' => $letter->uuid,
                        'path' => $letter->cv_path,
                    ]);
                }
            }
        }

        // ── Summary ────────────────────────────────────────────────
        $this->info(sprintf(
            '%s%d file(s) deleted, %d kept, %.2f MB %s.',
            $dry ? '[dry-run] ' : '',
            $deleted,
            $kept,
            $freed / 1024 / 1024,
            $dry ? 'would be freed' : 'freed'
        ));
        if (!$dry && $updatedLetters > 0) {
            $this->info("{$updatedLetters} stale letter(s) marked as failed (CV expired).");
        }

        if (!$dry) {
            Log::info('Letter temp CV cleanup complete', [
                'deleted'          => $deleted,
                'kept'             => $kept,
                'freed_mb'         => round($freed / 1024 / 1024, 2),
                'orphan_deleted'   => $reasons['orphan'],
                'expired_deleted'  => $reasons['expired'],
                'letters_updated'  => $updatedLetters,
                'max_age_days'     => $maxAgeDays,
            ]);
        }

        return self::SUCCESS;
    }
}