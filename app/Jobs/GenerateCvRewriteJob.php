<?php

namespace App\Jobs;

use App\Models\Service\CvReviewRequest;
use App\Services\Jobs\CvRewriteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateCvRewriteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 3;

    public function __construct(public int $cvReviewRequestId) {}

    public function backoff(): array { return [30, 120, 300]; }


    public function handle(CvRewriteService $service): void
    {
        $req = CvReviewRequest::find($this->cvReviewRequestId);

        if (!$req) {
            \Log::warning('GenerateCvRewriteJob: CV not found', [
                'id' => $this->cvReviewRequestId,
            ]);
            return;
        }

        // Already done — nothing to do.
        if ($req->status === 'generated') {
            \Log::info('GenerateCvRewriteJob: already generated, skipping', [
                'uuid' => $req->uuid,
            ]);
            return;
        }

        // Only workable statuses.
        if (!in_array($req->status, ['paid_ai', 'generating', 'failed'], true)) {
            \Log::warning('GenerateCvRewriteJob: wrong status, skipping', [
                'uuid'   => $req->uuid,
                'status' => $req->status,
            ]);
            return;
        }

        // Take the lock (idempotent — kick()/webhook may have already set it).
        // This blocks duplicate dispatches while we run.
        \Cache::put("cv-rewrite-running:{$req->id}", true, 300);

        try {
            $service->generate($req);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('GenerateCvRewriteJob: network error, will retry', [
                'uuid'    => $req->uuid,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);
            throw $e;   // finally still runs, then Laravel retries
        } catch (\Throwable $e) {
            \Log::warning('GenerateCvRewriteJob: terminal failure', [
                'uuid'    => $req->uuid,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);
        } finally {
            // ALWAYS release the lock — success, retry, or terminal failure.
            \Cache::forget("cv-rewrite-running:{$req->id}");
        }
    }

    public function failed(?Throwable $e): void
    {
        $req = CvReviewRequest::find($this->cvReviewRequestId);
        if (!$req) return;

        // If the job was killed mid-run, the finally block might not have fired.
        \Cache::forget("cv-rewrite-running:{$req->id}");

        // Only set a generic message if the service didn't already mark it failed.
        if (in_array($req->status, ['paid_ai', 'generating'], true)) {
            $req->update([
                'status'                => 'failed',
                'rewrite_error_message' => $e?->getMessage()
                    ?? 'Generation failed after multiple attempts. Please try again.',
            ]);
        }
    }
    
}