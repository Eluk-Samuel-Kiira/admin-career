<?php

namespace App\Jobs;

use App\Services\Jobs\AiService;
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

    public int $timeout = 900;
    public int $tries   = 1;

    public function __construct(
        public string $token,
        public string $content,
        public string $sourceType,
        public ?string $model,
        public ?string $country,
        public ?string $position,
    ) {}

    public function handle(AiService $ai): void
    {
        $key = "job_extract:{$this->token}";
        Cache::put($key, ['status' => 'processing'], 1800);

        try {
            $data = $ai->extractJobData($this->content, $this->sourceType, $this->model, $this->country, $this->position);
            Cache::put($key, ['status' => 'done', 'data' => $data], 1800);
        } catch (\Throwable $e) {
            Log::warning('ExtractJobPosting failed', ['token' => $this->token, 'error' => $e->getMessage()]);
            Cache::put($key, ['status' => 'failed', 'error' => $e->getMessage(), 'model' => $this->model], 1800);
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put("job_extract:{$this->token}", ['status' => 'failed', 'error' => $e->getMessage()], 1800);
    }
}