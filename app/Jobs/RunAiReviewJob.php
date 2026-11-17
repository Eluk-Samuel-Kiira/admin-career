<?php

namespace App\Jobs;

use App\Models\Service\CvReviewRequest;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RunAiReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(public int $cvReviewRequestId) {}

    public function handle(CvTextExtractionService $extractor, AiService $ai): void
    {
        $req = CvReviewRequest::find($this->cvReviewRequestId);
        if (!$req) return;

        if (!in_array($req->status, ['submitted', 'ai_reviewed'], true)) {
            return;
        }

        $path = $req->original_cv_path ?: $req->seeker_cv_path;
        if (!$path || !Storage::disk('public')->exists($path)) {
            Log::warning('RunAiReviewJob: CV missing', ['id' => $req->id, 'path' => $path]);
            return;
        }

        try {
            $text = $extractor->extract(
                Storage::disk('public')->path($path),
                Storage::disk('public')->mimeType($path) ?? 'application/pdf'
            );

            if (trim($text) === '') {
                Log::warning('RunAiReviewJob: no text from CV', ['id' => $req->id]);
                return;
            }

            $review = $ai->reviewCvGaps($text, $req->target_job_description);

            if (empty($review)) {
                Log::warning('RunAiReviewJob: empty AI review', ['id' => $req->id]);
                return;
            }

            $updates = [
                'ai_gap_review' => $review,
                'status'        => 'ai_reviewed',
            ];

            $log = $req->delivery_log ?? [];
            $log[] = [
                'at'   => now()->toISOString(),
                'type' => 'ai_review_run',
                'by'   => auth()->id(),
                'score' => $review['overall_score'] ?? null,
            ];
            $updates['delivery_log'] = $log;

            $req->update($updates);

            Log::info('RunAiReviewJob: complete', ['id' => $req->id]);
        } catch (Throwable $e) {
            Log::error('RunAiReviewJob failed', [
                'id'    => $req->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}