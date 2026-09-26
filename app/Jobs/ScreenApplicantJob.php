<?php

namespace App\Jobs;

use App\Models\Job\JobSeekerJob;
use App\Models\Job\JobScreeningBatch;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScreenApplicantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(
        public int $applicantId,
        public int $batchId,
    ) {}

    public function handle(
        AiService $ai,
        CvTextExtractionService $extractor,
    ): void {
        $batch = JobScreeningBatch::find($this->batchId);
        $applicant = JobSeekerJob::with(['seekerProfile', 'jobPost'])->find($this->applicantId);

        if (!$batch || !$applicant) {
            Log::warning('ScreenApplicantJob: missing batch or applicant', [
                'batch_id'     => $this->batchId,
                'applicant_id' => $this->applicantId,
            ]);
            return;
        }

        $batch->markStarted();

        try {
            $job = $applicant->jobPost;
            if (!$job) {
                throw new \Exception('Job post not found.');
            }

            // ── 1. Resolve the CV text (multi-source, fault-tolerant)
            $cvText = $this->resolveCvText($applicant, $extractor);

            if (empty($cvText)) {
                throw new \Exception('No CV source available (no file, no extracted text).');
            }

            // ── 2. Call AI
            $result = $ai->screenApplicant($cvText, [
                'job_title'        => $job->job_title,
                'job_description'  => $job->job_description,
                'responsibilities' => $job->responsibilities,
                'qualifications'   => $job->qualifications,
                'skills'           => $job->skills,
            ]);

            // ── 3. Persist
            $applicant->update([
                'ai_score'           => $result['score'],
                'ai_recommendation'  => $result['recommendation'],
                'ai_summary'         => $result['summary'],
                'ai_strengths'       => $result['strengths'],
                'ai_gaps'            => $result['gaps'],
                'ai_red_flags'       => $result['red_flags'],
                'ai_screened_at'     => now(),
                'ai_job_fingerprint' => $batch->job_fingerprint,
            ]);

            $batch->recordCompletion(true);

            // Log::info('ScreenApplicantJob: success', [
            //     'applicant_id' => $this->applicantId,
            //     'score'        => $result['score'],
            // ]);

        } catch (\Throwable $e) {
            Log::warning('ScreenApplicantJob: failed', [
                'applicant_id' => $this->applicantId,
                'error'        => $e->getMessage(),
            ]);

            $batch->recordFailure($this->applicantId);
            $batch->recordCompletion(false);
        }
    }


    
    /**
     * Resolve CV text from every available source, in priority order:
     *   1. cv_used_path (the CV used to apply)
     *   2. seeker.cv_file_path
     *   3. seeker.cv_files[] (try each)
     *   4. Structured profile text (fallback if no file extracts)
     *
     * Returns empty string if nothing usable is found.
     */
    private function resolveCvText(JobSeekerJob $applicant, CvTextExtractionService $extractor): string
    {
        $seeker = $applicant->seekerProfile;
        if (!$seeker) {
            \Log::warning('ScreenApplicantJob: no seeker profile', ['applicant_id' => $applicant->id]);
            return '';
        }

        // ── Build the candidate path list
        $paths = [];

        if (!empty($applicant->cv_used_path)) {
            $paths[] = $applicant->cv_used_path;
        }

        if (!empty($seeker->cv_file_path)) {
            $paths[] = $seeker->cv_file_path;
        }

        foreach ($this->getCvFilesArray($seeker) as $entry) {
            if (!empty($entry['path'])) {
                $paths[] = $entry['path'];
            }
        }

        $paths = array_values(array_unique($paths));

        // \Log::info('ScreenApplicantJob: candidate paths', [
        //     'applicant_id' => $applicant->id,
        //     'seeker_id'    => $seeker->id,
        //     'paths'        => $paths,
        // ]);

        // ── Try each file path, return on first success
        foreach ($paths as $path) {
            $text = $this->tryExtractFromPath($path, $extractor);
            if ($text !== '') {
                // \Log::info('ScreenApplicantJob: CV text resolved', [
                //     'applicant_id' => $applicant->id,
                //     'path'         => $path,
                //     'chars'        => mb_strlen($text),
                // ]);
                return $text;
            }
        }

        // ── Fallback: build text from the structured profile
        $profileText = $this->buildProfileText($seeker);
        if ($profileText !== '') {
            // \Log::info('ScreenApplicantJob: using structured profile as CV fallback', [
            //     'applicant_id' => $applicant->id,
            //     'chars'        => mb_strlen($profileText),
            // ]);
            return $profileText;
        }

        // ── Nothing worked
        \Log::warning('ScreenApplicantJob: no CV source available', [
            'applicant_id' => $applicant->id,
            'paths_tried'  => $paths,
        ]);

        return '';
    }

    /**
     * Try to read and extract text from a single CV path.
     * Returns '' on any failure (missing file, empty text, extraction error).
     */
    private function tryExtractFromPath(string $path, CvTextExtractionService $extractor): string
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                Log::info('ScreenApplicantJob: CV path missing on disk', ['path' => $path]);
                return '';
            }

            $absolutePath = Storage::disk('public')->path($path);
            $mimeType     = Storage::disk('public')->mimeType($path) ?? 'application/pdf';

            $text = $extractor->extract($absolutePath, $mimeType);
            $text = trim($text);

            // Log::info('ScreenApplicantJob: extraction attempt', [
            //     'path'  => $path,
            //     'chars' => mb_strlen($text),
            // ]);

            return $text;

        } catch (\Throwable $e) {
            Log::warning('ScreenApplicantJob: extraction threw', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }


    /**
     * Build a plain-text summary from the seeker's structured profile.
     * Used when no CV file can be extracted.
     */
    private function buildProfileText($seeker): string
    {
        $lines = [];

        if ($seeker->professional_title) {
            $lines[] = "Professional Title: {$seeker->professional_title}";
        }

        if ($seeker->professional_summary) {
            $lines[] = "Summary: {$seeker->professional_summary}";
        }

        if ($seeker->years_of_experience) {
            $lines[] = "Years of Experience: {$seeker->years_of_experience}";
        }

        $skills = $seeker->skills_array ?? [];
        if (!empty($skills)) {
            $lines[] = "Skills: " . implode(', ', $skills);
        }

        $languages = $seeker->languages_array ?? [];
        if (!empty($languages)) {
            $lines[] = "Languages: " . implode(', ', $languages);
        }

        $education = $seeker->education_array ?? [];
        if (!empty($education)) {
            $lines[] = "Education:";
            foreach ($education as $edu) {
                if (is_array($edu)) {
                    $parts = array_filter([
                        $edu['degree'] ?? null,
                        $edu['institution'] ?? null,
                        $edu['field'] ?? null,
                        $edu['end_year'] ?? null,
                    ]);
                    $lines[] = '  - ' . implode(' · ', $parts);
                } elseif (is_string($edu)) {
                    $lines[] = "  - {$edu}";
                }
            }
        }

        $work = $seeker->work_experience_array ?? [];
        if (!empty($work)) {
            $lines[] = "Work Experience:";
            foreach ($work as $exp) {
                if (is_array($exp)) {
                    $parts = array_filter([
                        $exp['title'] ?? null,
                        $exp['company'] ?? null,
                        $exp['start_date'] ?? null,
                        $exp['end_date'] ?? null,
                    ]);
                    $lines[] = '  - ' . implode(' · ', $parts);
                    if (!empty($exp['description'])) {
                        $lines[] = '    ' . strip_tags($exp['description']);
                    }
                } elseif (is_string($exp)) {
                    $lines[] = "  - {$exp}";
                }
            }
        }

        $certs = $seeker->certifications_array ?? [];
        if (!empty($certs)) {
            $lines[] = "Certifications: " . implode(', ', array_map(
                fn($c) => is_array($c) ? ($c['name'] ?? '') : $c,
                $certs
            ));
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Safely get cv_files as an array, regardless of underlying storage type.
     */
    private function getCvFilesArray($seeker): array
    {
        $files = $seeker->cv_files ?? null;

        if (is_null($files)) return [];

        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($files)) return $files;

        return [];
    }
}