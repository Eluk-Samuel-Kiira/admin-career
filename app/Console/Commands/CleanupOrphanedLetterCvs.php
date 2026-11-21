<?php

namespace App\Console\Commands;

use App\Models\Service\CvReviewRequest;
use App\Models\Service\LetterRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedLetterCvs extends Command
{
    protected $signature = 'letters:cleanup-temp-cvs
                            {--hours=24 : Delete temp CVs older than this many hours (orphaned files only)}
                            {--max-age-days=3 : Hard cap — delete any uploaded temp CV older than this, regardless of status}
                            {--stale-days=14 : Delete unpaid cover letters and CV review requests older than this many days}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Clean up orphaned temp CVs, and purge unpaid cover letters + CV review requests older than N days';

    public function handle(): int
    {
        $hours      = (int) $this->option('hours');
        $maxAgeDays = (int) $this->option('max-age-days');
        $staleDays  = (int) $this->option('stale-days');
        $dry        = (bool) $this->option('dry-run');

        $orphanCutoff = now()->subHours($hours);
        $hardCutoff   = now()->subDays($maxAgeDays);
        $staleCutoff  = now()->subDays($staleDays);

        // ═══════════════════════════════════════════════════════
        // PASS 1 — temp CV files under tmp/letters/
        // ═══════════════════════════════════════════════════════
        $fileStats = $this->sweepTempCvFiles($orphanCutoff, $hardCutoff, $dry);

        // ═══════════════════════════════════════════════════════
        // PASS 2 — stale LetterRequest rows (unpaid)
        // ═══════════════════════════════════════════════════════
        $letterStats = $this->purgeStaleLetters($staleCutoff, $dry);

        // ═══════════════════════════════════════════════════════
        // PASS 3 — stale CvReviewRequest rows (not paid / not delivered)
        // ═══════════════════════════════════════════════════════
        $cvStats = $this->purgeStaleCvReviews($staleCutoff, $dry);

        // ═══════════════════════════════════════════════════════
        // Summary
        // ═══════════════════════════════════════════════════════
        $prefix = $dry ? '[dry-run] ' : '';

        $this->info($prefix . sprintf(
            'Temp CV files: %d deleted, %d kept, %.2f MB %s.',
            $fileStats['deleted'],
            $fileStats['kept'],
            $fileStats['freed'] / 1024 / 1024,
            $dry ? 'would be freed' : 'freed'
        ));

        $this->info($prefix . sprintf(
            'Stale cover letters (> %d days, unpaid): %d deleted, %d file(s) removed.',
            $staleDays,
            $letterStats['deleted'],
            $letterStats['files']
        ));

        $this->info($prefix . sprintf(
            'Stale CV review requests (> %d days, not paid/delivered): %d deleted, %d file(s) removed.',
            $staleDays,
            $cvStats['deleted'],
            $cvStats['files']
        ));

        if (!$dry) {
            Log::info('Cleanup command complete', [
                'temp_files' => $fileStats,
                'letters'    => $letterStats,
                'cv_reviews' => $cvStats,
                'stale_days' => $staleDays,
            ]);
        }

        return self::SUCCESS;
    }

    // ═══════════════════════════════════════════════════════════════
    // PASS 1 — temp CV files
    // ═══════════════════════════════════════════════════════════════
    private function sweepTempCvFiles($orphanCutoff, $hardCutoff, bool $dry): array
    {
        $keepPaths = LetterRequest::query()
            ->where('created_at', '>=', $hardCutoff)
            ->where(function ($q) {
                $q->whereIn('status', ['paid', 'processing'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'pending_payment');
                  })
                  ->orWhere(function ($q3) {
                      $q3->where('status', 'failed')
                         ->whereColumn('regeneration_count', '<', 'max_regenerations');
                  });
            })
            ->whereNotNull('cv_path')
            ->pluck('cv_path')
            ->all();

        $keep = array_flip($keepPaths);

        $disk    = Storage::disk('public');
        $all     = $disk->files('tmp/letters');
        $deleted = 0;
        $kept    = 0;
        $freed   = 0;

        foreach ($all as $path) {
            if (isset($keep[$path])) {
                $kept++;
                continue;
            }

            $mtime = $disk->lastModified($path);

            // Guard: don't delete orphans less than N hours old
            if ($mtime > $orphanCutoff->timestamp) {
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
                Log::warning('Failed to delete temp CV', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        // Second sweep — mark stale in-flight letters whose file was already deleted
        $updatedLetters = 0;
        if (!$dry) {
            $maxAgeDays = $this->maxAgeDaysSafe();
            $stale = LetterRequest::query()
                ->where('created_at', '<', $hardCutoff)
                ->whereIn('status', ['pending_payment', 'paid', 'processing'])
                ->whereNotNull('cv_path')
                ->get();

            foreach ($stale as $letter) {
                if (!Storage::disk('public')->exists($letter->cv_path)) {
                    $letter->update([
                        'status'        => 'failed',
                        'error_message' => "Your uploaded CV expired after {$maxAgeDays} days and was deleted. "
                            . "Please upload it again to regenerate.",
                    ]);
                    $updatedLetters++;
                }
            }
        }

        return ['deleted' => $deleted, 'kept' => $kept, 'freed' => $freed, 'letters_updated' => $updatedLetters];
    }

    private function maxAgeDaysSafe(): int
    {
        return (int) $this->option('max-age-days');
    }

    // ═══════════════════════════════════════════════════════════════
    // PASS 2 — stale unpaid cover letters
    // ═══════════════════════════════════════════════════════════════
    private function purgeStaleLetters($staleCutoff, bool $dry): array
    {
        $query = LetterRequest::query()
            ->where('created_at', '<', $staleCutoff)
            ->where(function ($q) {
                // Not paid → safe to delete
                $q->whereIn('status', ['pending_payment', 'failed'])
                  // Or stuck in processing/paid but older than the cutoff — the generation
                // worker is definitely dead if the row hasn't moved in 14 days
                  ->orWhere(function ($q2) {
                      $q2->whereIn('status', ['paid', 'processing'])
                         ->whereNull('generated_at')
                         ->where('updated_at', '<', now()->subDays(7));
                  });
            });

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return ['deleted' => 0, 'files' => 0];
        }

        $deleted = 0;
        $files   = 0;

        foreach ($rows as $letter) {
            if ($dry) {
                $this->line("[dry-run] would delete cover letter #{$letter->id} ({$letter->uuid})"
                    . " — status={$letter->status}, created=" . $letter->created_at->toDateString());
                $deleted++;
                continue;
            }

            $paths = $this->collectLetterFilePaths($letter);

            try {
                \DB::transaction(function () use ($letter, $paths, &$files) {
                    // Delete DB row
                    $letter->delete();

                    // Delete files (best-effort, never blocks the delete)
                    foreach ($paths as $p) {
                        try {
                            if (Storage::disk('public')->exists($p)) {
                                Storage::disk('public')->delete($p);
                                $files++;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to delete letter file', ['path' => $p, 'error' => $e->getMessage()]);
                        }
                    }
                });

                $deleted++;

                Log::info('Deleted stale cover letter', [
                    'uuid'       => $letter->uuid,
                    'user_id'    => $letter->user_id,
                    'status'     => $letter->status,
                    'created_at' => $letter->created_at->toISOString(),
                    'files'      => $paths,
                ]);

            } catch (\Throwable $e) {
                Log::error('Failed to delete stale cover letter', [
                    'uuid'  => $letter->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['deleted' => $deleted, 'files' => $files];
    }

    private function collectLetterFilePaths(LetterRequest $letter): array
    {
        $paths = [];

        // Uploaded temp CV
        if (!empty($letter->cv_path) && str_starts_with($letter->cv_path, 'tmp/letters/')) {
            $paths[] = $letter->cv_path;
        }

        // Any PDF we generated for the delivery
        if (!empty($letter->content)) {
            // content is stored as HTML, no path — nothing to delete
        }

        return $paths;
    }

    // ═══════════════════════════════════════════════════════════════
    // PASS 3 — stale CvReviewRequest rows
    // ═══════════════════════════════════════════════════════════════
    private function purgeStaleCvReviews($staleCutoff, bool $dry): array
    {
        // Statuses that mean "the client never completed the pipeline"
        $staleStatuses = [
            'submitted',        // admin created, nothing further
            'ai_reviewed',      // review ran, but user never answered
            'awaiting_payment', // user answered but never paid
            'paid_human',       // human path, but stuck without progress for 14 days
            'revision_requested', // stuck in revision hell
        ];

        $query = CvReviewRequest::query()
            ->where('created_at', '<', $staleCutoff)
            ->where(function ($q) use ($staleStatuses) {
                $q->whereIn('status', $staleStatuses)
                  // Even failed AI generation — if it's 14 days old, forget it
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'failed')
                         ->whereNull('delivered_at');
                  })
                  // Paid, but nothing generated in 7 days — the worker is dead
                  ->orWhere(function ($q3) {
                      $q3->whereIn('status', ['paid', 'paid_ai', 'generating'])
                         ->whereNull('delivered_at')
                         ->whereNull('rewrite_generated_at')
                         ->where('updated_at', '<', now()->subDays(7));
                  });
            });

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return ['deleted' => 0, 'files' => 0];
        }

        $deleted = 0;
        $files   = 0;

        foreach ($rows as $req) {
            if ($dry) {
                $this->line("[dry-run] would delete CV review #{$req->id} ({$req->uuid})"
                    . " — status={$req->status}, created=" . $req->created_at->toDateString());
                $deleted++;
                continue;
            }

            $paths = $this->collectCvReviewFilePaths($req);

            try {
                \DB::transaction(function () use ($req, $paths, &$files) {
                    // Linked cover letter — delete it too (if this request created one)
                    if (!empty($req->cover_letter_uuid)) {
                        $letter = \App\Models\Service\LetterRequest::where('uuid', $req->cover_letter_uuid)->first();
                        if ($letter) {
                            $letterPaths = $this->collectLetterFilePaths($letter);
                            $letter->delete();

                            foreach ($letterPaths as $lp) {
                                try {
                                    if (Storage::disk('public')->exists($lp)) {
                                        Storage::disk('public')->delete($lp);
                                        $files++;
                                    }
                                } catch (\Throwable $e) {
                                    Log::warning('Failed to delete letter file (cascade)', ['path' => $lp]);
                                }
                            }
                        }
                    }

                    $req->delete();

                    foreach ($paths as $p) {
                        try {
                            if (Storage::disk('public')->exists($p)) {
                                Storage::disk('public')->delete($p);
                                $files++;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to delete CV review file', ['path' => $p, 'error' => $e->getMessage()]);
                        }
                    }
                });

                $deleted++;

                Log::info('Deleted stale CV review request', [
                    'uuid'       => $req->uuid,
                    'user_id'    => $req->user_id,
                    'status'     => $req->status,
                    'created_at' => $req->created_at->toISOString(),
                    'files'      => $paths,
                ]);

            } catch (\Throwable $e) {
                Log::error('Failed to delete stale CV review', [
                    'uuid'  => $req->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['deleted' => $deleted, 'files' => $files];
    }

    private function collectCvReviewFilePaths(CvReviewRequest $req): array
    {
        $paths = [];

        foreach (['original_cv_path', 'seeker_cv_path', 'delivered_cv_path'] as $field) {
            if (!empty($req->{$field}) && Storage::disk('public')->exists($req->{$field})) {
                $paths[] = $req->{$field};
            }
        }

        // Whole uuid folder if it exists (cv-review/{uuid}/*)
        $folder = "cv-review/{$req->uuid}";
        if (Storage::disk('public')->exists($folder)) {
            try {
                foreach (Storage::disk('public')->allFiles($folder) as $f) {
                    $paths[] = $f;
                }
            } catch (\Throwable $e) {
                // ignore — the individual file delete below will still try
            }
        }

        return array_values(array_unique($paths));
    }
}