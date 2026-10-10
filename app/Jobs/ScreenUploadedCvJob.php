<?php

namespace App\Jobs;

use App\Models\Hiring\HiringRun;
use App\Models\Hiring\HiringRunCandidate;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScreenUploadedCvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(public int $candidateId) {}

    public function handle(AiService $ai, CvTextExtractionService $extractor): void
    {
        $candidate = HiringRunCandidate::with('run')->find($this->candidateId);
        if (!$candidate || !$candidate->run) return;

        $run = $candidate->run;
        $candidate->update(['status' => 'processing']);

        try {
            // 1. Extract text
            $absolute = Storage::disk('public')->path($candidate->stored_path);
            $mime = Storage::disk('public')->mimeType($candidate->stored_path) ?: 'application/pdf';
            $cvText = trim($extractor->extract($absolute, $mime));

            if (mb_strlen($cvText) < 100) {
                throw new \Exception('Could not read enough text from the CV file.');
            }

            // 2. Score against the job description
            $jobContext = $this->buildJobContext($run);

            $result = $ai->screenUploadedCv($cvText, $jobContext);

            // 3. Persist
            $candidate->update([
                'candidate_name'       => $result['candidate_name']       ?? null,
                'candidate_email'      => $result['candidate_email']      ?? null,
                'candidate_phone'      => $result['candidate_phone']      ?? null,
                'current_title'        => $result['current_title']        ?? null,
                'years_of_experience'  => $result['years_of_experience']  ?? null,
                'highest_education'    => $result['highest_education']    ?? null,
                'score'                => $result['score'],
                'recommendation'       => $result['recommendation'],
                'summary'              => $result['summary'],
                'strengths'            => $result['strengths'],
                'gaps'                 => $result['gaps'],
                'red_flags'            => $result['red_flags'],
                'matched_skills'       => $result['matched_skills'],
                'missing_skills'       => $result['missing_skills'],
                'status'               => 'completed',
                'screened_at'          => now(),
            ]);

            $run->increment('processed');
            $this->finishRunIfDone($run);

        } catch (\Throwable $e) {
            Log::warning('ScreenUploadedCvJob failed', [
                'candidate_id' => $candidate->id,
                'error'        => $e->getMessage(),
            ]);

            $candidate->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $run->increment('failed');
            $this->finishRunIfDone($run);
        }
    }

    protected function buildJobContext(HiringRun $run): array
    {
        return [
            'job_title'       => $run->title,
            'job_description' => $run->job_description,
        ];
    }

    protected function finishRunIfDone(HiringRun $run): void
    {
        $run->refresh();
        if ($run->processed + $run->failed >= $run->total) {
            $run->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}