<?php

namespace App\Jobs;

use App\Services\Jobs\AiJobService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExtractJobPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;          // the extractor itself gives up after about 4 minutes
    public int $tries   = 1;
    public bool $failOnTimeout = true;

    public function __construct(
        public string $token,
        public string $content,
        public string $sourceType,
        public ?string $model,
        public ?string $country,
        public ?string $position,
    ) {}

    public function handle(AiJobService $ai): void
    {
        $key = "job_extract:{$this->token}";
        Cache::put($key, ['status' => 'processing'], 1800);

        $t0 = microtime(true);

        try {
            $data = $ai->extractJobData($this->content, $this->sourceType, $this->model, $this->country, $this->position);
            Cache::put($key, ['status' => 'done', 'data' => $data], 1800);

            Log::info('ExtractJobPosting done in ' . round(microtime(true) - $t0, 1) . 's', ['token' => $this->token]);
        } catch (\Throwable $e) {
            Log::warning('ExtractJobPosting failed after ' . round(microtime(true) - $t0, 1) . 's', [
                'token' => $this->token,
                'error' => mb_substr($e->getMessage(), 0, 600),
            ]);
            Cache::put($key, ['status' => 'failed', 'error' => $e->getMessage(), 'model' => $this->model], 1800);
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put("job_extract:{$this->token}", [
            'status' => 'failed',
            'error'  => $e->getMessage(),
            'model'  => $this->model,
        ], 1800);
    }
}