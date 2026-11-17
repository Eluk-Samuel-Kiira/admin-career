<?php

namespace App\Jobs;

use App\Models\Service\CvReviewRequest;
use App\Models\Service\LetterRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateCoverLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout        = 60;
    public int $tries          = 20;      // ← was 2 — allow 10 min of waiting
    public array $backoff      = [30];    // ← was [60] — check every 30s
    public int $maxExceptions  = 3;       // ← real errors fail fast

    public function __construct(public int $cvReviewRequestId) {}

    public function handle(): void
    {
        // ── 1. Pre-flight (no transaction) ──
        $cv = CvReviewRequest::with(['user.seekerProfile', 'currency'])
            ->find($this->cvReviewRequestId);

        if (!$cv) {
            throw new \RuntimeException("CV review #{$this->cvReviewRequestId} was not found.");
        }

        if (!empty($cv->cover_letter_uuid)) {
            Log::info('GenerateCoverLetterJob: already linked', ['cv_id' => $cv->id]);
            return;
        }

        // CV still generating → wait, don't fail
        if (in_array($cv->status, ['paid_ai', 'generating'], true)) {
            Log::info('GenerateCoverLetterJob: CV still generating, releasing', [
                'cv_id'     => $cv->id,
                'cv_status' => $cv->status,
                'attempt'   => $this->attempts(),
                'tries'     => $this->tries,
            ]);
            $this->release(30);
            return;
        }

        // CV failed / not generated → real failure
        $cvHtml = trim((string) ($cv->rewrite_content['html'] ?? ''));
        if ($cv->status !== 'generated' || $cvHtml === '') {
            throw new \RuntimeException(
                'Cover letter stopped: the CV was not generated. '
                . ($cv->rewrite_error_message
                    ? 'CV error: ' . $cv->rewrite_error_message
                    : 'CV status: ' . $cv->status)
            );
        }

        // ── 2. Create the letter in a short transaction ──
        $letterId = DB::transaction(function () use ($cv) {
            $locked = CvReviewRequest::whereKey($cv->id)->lockForUpdate()->first();

            // Race check — another worker created it
            if (!empty($locked->cover_letter_uuid)) {
                Log::info('GenerateCoverLetterJob: race lost, already linked', ['cv_id' => $cv->id]);
                return null;
            }

            // Nothing to write for
            if (!$cv->job_post_id && empty($cv->target_job_title) && empty($cv->target_job_description)) {
                $this->appendLog($cv, 'cover_letter_skipped', 'No target job on this request.');
                return null;
            }

            $letter = LetterRequest::create([
                'user_id'           => $cv->user_id,
                'seeker_profile_id' => $cv->user?->seekerProfile?->id,
                'cv_source'         => 'existing',
                'cv_path'           => $cv->original_cv_path ?: $cv->seeker_cv_path,
                'job_post_id'       => $cv->job_post_id,
                'job_title'         => $cv->target_job_title ?: 'Untitled role',
                'company_name'      => $cv->target_company_name ?: 'Company',
                'job_description'   => $cv->target_job_description,
                'letter_type'       => 'cover',
                'price'             => 0,
                'currency'          => $cv->currency?->code ?: 'UGX',
                'status'            => 'paid',
                'payment_reference' => 'CVREV-' . $cv->uuid,
                'paid_at'           => now(),
            ]);

            $locked->update(['cover_letter_uuid' => $letter->uuid]);
            $this->appendLog($cv, 'cover_letter_started', 'Cover letter created from the generated CV.', [
                'letter_uuid' => $letter->uuid,
            ]);

            return $letter->id;
        });

        // ── 3. Dispatch outside the transaction ──
        if ($letterId) {
            try {
                GenerateLetterJob::dispatch($letterId)->onQueue('default');
            } catch (\Throwable $e) {
                Log::error('GenerateCoverLetterJob: dispatch failed', [
                    'letter_id' => $letterId,
                    'error'     => $e->getMessage(),
                ]);
                LetterRequest::whereKey($letterId)->update([
                    'status'        => 'failed',
                    'error_message' => 'Queue dispatch failed: ' . mb_substr($e->getMessage(), 0, 200),
                ]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $cv = CvReviewRequest::find($this->cvReviewRequestId);
        if (!$cv) return;

        $this->appendLog($cv, 'cover_letter_failed', mb_substr($e->getMessage(), 0, 500));
    }

    private function appendLog(CvReviewRequest $cv, string $type, string $note, array $extra = []): void
    {
        $log   = $cv->delivery_log ?? [];
        $log[] = array_merge([
            'at'   => now()->toISOString(),
            'type' => $type,
            'note' => $note,
        ], $extra);

        $cv->update(['delivery_log' => $log]);
    }
}