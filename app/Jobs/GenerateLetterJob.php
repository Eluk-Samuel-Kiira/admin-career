<?php

namespace App\Jobs;

use App\Models\Service\LetterRequest;
use App\Services\Jobs\LetterGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(public int $letterRequestId) {}

    public function handle(LetterGeneratorService $service): void
    {
        $request = LetterRequest::with(['seekerProfile', 'jobPost'])->find($this->letterRequestId);

        if (!$request) {
            Log::warning('GenerateLetterJob: request not found', ['id' => $this->letterRequestId]);
            return;
        }

        if ($request->status === 'generated') {
            return;
        }

        $service->generate($request);
    }

    public function failed(\Throwable $e): void
    {
        $request = LetterRequest::find($this->letterRequestId);
        if ($request && $request->status !== 'failed') {
            $request->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}