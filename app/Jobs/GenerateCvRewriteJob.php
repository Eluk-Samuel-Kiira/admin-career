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
        if (!$req) return;

        if ($req->status === 'generated') return;
        if (!in_array($req->status, ['paid_ai', 'generating', 'failed'], true)) return;

        // If it's failed but the error was transient and retries remain, allow re-attempt
        if ($req->status === 'failed' && $req->rewrite_error_message) {
            $isTransient = (bool) preg_match('/\b(503|429|UNAVAILABLE|high demand|timeout|quota)\b/i', $req->rewrite_error_message);
            if (!$isTransient || $this->attempts() > $this->tries) {
                return;   // terminal, don't retry
            }
        }

        try {
            $service->generate($req);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network — let the queue retry
            throw $e;
        } catch (\Throwable $e) {
            // Terminal — log, service has already set status='failed'
            Log::warning('GenerateCvRewriteJob: terminal failure', [
                'uuid'    => $req->uuid,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $e): void
    {
        $req = CvReviewRequest::find($this->cvReviewRequestId);
        if (!$req) return;

        // If the service already marked it 'failed' with a specific error, keep that.
        // Only set our generic message if the status is still 'paid_ai'/'generating'.
        if (in_array($req->status, ['paid_ai', 'generating'], true)) {
            $req->update([
                'status'                => 'failed',
                'rewrite_error_message' => 'Generation failed after multiple attempts. Please try again.',
            ]);
        }
    }
    
}