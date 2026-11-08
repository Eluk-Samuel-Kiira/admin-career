<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractJobPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;
    public int $tries = 1;

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
        try {
            $data = $ai->extractJobData($this->content, $this->sourceType, $this->model, $this->country, $this->position);
            Cache::put("job_extract:{$this->token}", ['status' => 'done', 'data' => $data], 900);
        } catch (\Throwable $e) {
            Cache::put("job_extract:{$this->token}", ['status' => 'failed', 'message' => $e->getMessage()], 900);
        }
    }
}