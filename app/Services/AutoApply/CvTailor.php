<?php

namespace App\Services\AutoApply;

use App\Models\SeekerProfile;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvTailor
{
    public function __construct(
        protected AiService $ai,
        protected CvTextExtractionService $extractor,
    ) {}

    /**
     * Produce a tailored CV for the job.
     *
     * Returns ['html' => string, 'path' => string] where path is on the public disk.
     */
    public function tailor(SeekerProfile $seeker, array $jobContext): array
    {
        $cvText = $this->resolveCvText($seeker);

        if ($cvText === '') {
            throw new \Exception('No CV text available for tailoring.');
        }

        $html = $this->ai->tailorCv($cvText, $jobContext);

        // Render to PDF using your existing DomPDF wiring (pdf.auto-apply.tailored-cv view).
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.auto-apply.tailored-cv', [
            'content' => $html,
            'seeker'  => $seeker,
            'job'     => $jobContext,
        ])->setPaper('a4');

        $path = 'auto-apply/' . Str::uuid() . '/tailored-cv.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return ['html' => $html, 'path' => $path];
    }

    protected function resolveCvText(SeekerProfile $seeker): string
    {
        $paths = array_filter([
            $seeker->cv_file_path,
            ...array_map(
                fn($f) => is_array($f) ? ($f['path'] ?? null) : null,
                is_array($seeker->cv_files) ? $seeker->cv_files : []
            ),
        ]);

        foreach ($paths as $path) {
            if (!Storage::disk('public')->exists($path)) continue;

            try {
                $absolute = Storage::disk('public')->path($path);
                $mime = Storage::disk('public')->mimeType($path) ?: 'application/pdf';
                $text = trim($this->extractor->extract($absolute, $mime));
                if ($text !== '') return $text;
            } catch (\Throwable $e) {
                \Log::warning('Auto-apply: CV extraction failed', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fall back to a structured profile summary
        return $this->buildProfileText($seeker);
    }

    protected function buildProfileText(SeekerProfile $seeker): string
    {
        $lines = array_filter([
            $seeker->professional_title   ? "Title: {$seeker->professional_title}" : null,
            $seeker->professional_summary ? "Summary: {$seeker->professional_summary}" : null,
            $seeker->years_of_experience  ? "Years of Experience: {$seeker->years_of_experience}" : null,
            !empty($seeker->skills_array) ? 'Skills: ' . implode(', ', $seeker->skills_array) : null,
        ]);

        return implode("\n", $lines);
    }
}