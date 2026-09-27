<?php

namespace App\Services\Jobs;

use App\Models\Service\LetterRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LetterGeneratorService
{
    public function __construct(
        protected AiService $ai,
        protected CvTextExtractionService $extractor,
    ) {}

    public function generate(LetterRequest $request): void
    {
        $request->update(['status' => 'processing', 'error_message' => null]);

        try {
            $cvText = $this->resolveCvText($request);

            if ($cvText === '' || !$this->isPlausibleCv($cvText)) {
                throw new \Exception('The provided CV could not be read as a real CV.');
            }

            $jobContext = [
                'job_title'       => $request->job_title,
                'company_name'    => $request->company_name,
                'job_description' => $this->resolveJobDescription($request),
            ];

            $html = $this->ai->generateLetter($cvText, $jobContext, $request->letter_type);

            $request->update([
                'content'      => $html,
                'status'       => 'generated',
                'generated_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::warning('LetterGeneratorService failed', [
                'uuid'  => $request->uuid,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;

        } finally {
            $this->cleanupTempCv($request);
        }
    }

    protected function resolveCvText(LetterRequest $request): string
    {
        $path = $request->cv_path;

        if ($path && Storage::disk('public')->exists($path)) {
            $text = $this->tryExtract($path);
            if ($text !== '') {
                return $text;
            }
        }

        $seeker = $request->seekerProfile;
        if ($seeker && $seeker->cv_file_path && Storage::disk('public')->exists($seeker->cv_file_path)) {
            $text = $this->tryExtract($seeker->cv_file_path);
            if ($text !== '') {
                return $text;
            }
        }

        if ($seeker && is_array($seeker->cv_files)) {
            foreach ($seeker->cv_files as $entry) {
                if (!empty($entry['path']) && Storage::disk('public')->exists($entry['path'])) {
                    $text = $this->tryExtract($entry['path']);
                    if ($text !== '') {
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    protected function tryExtract(string $path): string
    {
        try {
            $absolutePath = Storage::disk('public')->path($path);
            $mime = Storage::disk('public')->mimeType($path) ?: 'application/pdf';
            $text = $this->extractor->extract($absolutePath, $mime);
            return trim($text);
        } catch (\Throwable $e) {
            Log::info('Letter CV extraction failed', ['path' => $path, 'error' => $e->getMessage()]);
            return '';
        }
    }

    public function isPlausibleCv(string $text): bool
    {
        $text = trim($text);
        if (mb_strlen($text) < 200) return false;

        $words = preg_split('/\s+/', $text);
        if (count($words) < 60) return false;

        $markers = [
            'experience', 'education', 'skills', 'employment',
            'university', 'college', 'degree', 'diploma', 'certificate',
            'worked', 'managed', 'developed', 'responsible',
            'references', 'objective', 'profile', 'summary',
            'curriculum vitae', 'resume', 'cv',
        ];

        $lower = mb_strtolower($text);
        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $text)
            && preg_match('/\b(19|20)\d{2}\b/', $text)) {
            return true;
        }

        return false;
    }

    protected function resolveJobDescription(LetterRequest $request): string
    {
        if ($request->job_post_id && $request->jobPost) {
            $job = $request->jobPost;
            $parts = array_filter([
                strip_tags($job->job_description ?? ''),
                strip_tags($job->responsibilities ?? ''),
                strip_tags($job->qualifications ?? ''),
                $job->skills ? 'Skills: ' . $job->skills : null,
            ]);
            return implode("\n\n", $parts);
        }

        return (string) ($request->job_description ?? '');
    }

    protected function cleanupTempCv(LetterRequest $request): void
    {
        if ($request->cv_source !== 'uploaded') return;

        $path = $request->cv_path;
        if (!$path) return;

        if (!str_starts_with($path, 'tmp/letters/')) return;

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::info('Letter temp CV cleanup failed', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }
}