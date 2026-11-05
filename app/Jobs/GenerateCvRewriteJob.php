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

        // ── Lock so `kick()` knows not to re-dispatch while we run ──
        \Cache::put("cv-rewrite-running:{$req->id}", true, 300);

        try {
            $service->generate($req);
        } catch (ConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            \Log::warning('GenerateCvRewriteJob: terminal failure', [
                'uuid'    => $req->uuid,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);
        } finally {
            \Cache::forget("cv-rewrite-running:{$req->id}");
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