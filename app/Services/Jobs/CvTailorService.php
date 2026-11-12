<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CvTailorService
{
    public function __construct(
        protected GeminiLetterRotator $rotator,
        protected CvTextExtractionService $extractor,
    ) {}

    /**
     * Tailor a CV for a specific job.
     *
     * Returns ['html' => string, 'words' => int]
     *
     * @throws \Exception when the AI fails or the output fails sanity checks
     */
    public function tailor(
        string $cvText, 
        string $jobTitle, 
        string $companyName, 
        string $jobDescription = '',
        array $dbProfile = [] // <--- New parameter
    ): array {
        $start = microtime(true);

        // Build the combined data source
        $sourceText = "=== DATABASE PROFILE DATA ===\n";
        if (!empty($dbProfile)) {
            $sourceText .= json_encode($dbProfile, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            $sourceText .= "No database profile data available.\n\n";
        }

        $sourceText .= "=== EXTRACTED CV TEXT ===\n";
        if (trim($cvText) !== '') {
            $sourceText .= trim($cvText) . "\n";
        } else {
            $sourceText .= "No uploaded CV file text could be extracted.\n";
        }

        // Build prompt
        $prompt = $this->buildPrompt($sourceText, $jobTitle, $companyName, $jobDescription);

        Log::info('CvTailorService: calling AI', [
            'job_title'    => $jobTitle,
            'has_db_data'  => !empty($dbProfile),
            'has_cv_text'  => trim($cvText) !== '',
        ]);

        $raw  = $this->rotator->generate($prompt, 4096);
        $html = CvHtmlSanitizer::clean($raw);

        $words = str_word_count(strip_tags($html));
        if ($words < 100) {
            throw new \Exception("Tailored CV returned too short a response ({$words} words).");
        }

        Log::info('CvTailorService: complete', [
            'job_title' => $jobTitle,
            'words'     => $words,
            'secs'      => round(microtime(true) - $start, 2),
        ]);

        return ['html' => $html, 'words' => $words];
    }

    /**
     * Extract CV text from a seeker profile's files.
     * Tries cv_file_path first, then cv_files array, then falls back to profile summary.
     */
    public function resolveCvText($seeker): string
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
                Log::warning('CvTailorService: extraction failed', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->buildProfileText($seeker);
    }

    protected function buildProfileText($seeker): string
    {
        $lines = array_filter([
            $seeker->professional_title   ? "Title: {$seeker->professional_title}" : null,
            $seeker->professional_summary ? "Summary: {$seeker->professional_summary}" : null,
            $seeker->years_of_experience  ? "Years of Experience: {$seeker->years_of_experience}" : null,
            !empty($seeker->skills_array) ? 'Skills: ' . implode(', ', $seeker->skills_array) : null,
        ]);

        return implode("\n", $lines);
    }

    /**
     * The gold-standard prompt — copied from CvRewriteService::buildRewritePrompt().
     * This is the one that doesn't invent facts.
     */
    protected function buildPrompt(
        string $cvText,
        string $jobTitle,
        string $companyName,
        string $jobDesc
    ): string {
        $cleanDesc = trim(strip_tags($jobDesc));
        $sourceData = trim($cvText) === '' 
            ? "NO CV TEXT PROVIDED. You must use placeholders [.....] for all details."
            : $cvText;

        return <<<PROMPT
            You are a senior CV writer. Your task is to produce a tailored CV for a specific job.

            ═══════════════════════════════════════════════════════════════
            RULES FOR DATA EXTRACTION & PLACEHOLDERS
            ═══════════════════════════════════════════════════════════════
            1. Extract facts ONLY from the provided Seeker Data. Do not invent employers, dates, degrees, or numbers.
            2. If a required section is missing (e.g., Referees, specific dates), write "[.....]" instead of leaving it blank or inventing it.
            3. Tailor the CV to match the Target Job's requirements. Highlight the skills the job asks for that the candidate actually has.

            ═══════════════════════════════════════════════════════════════
            TARGET JOB DETAILS
            ═══════════════════════════════════════════════════════════════
            Job Title: {$jobTitle}
            Company: {$companyName}
            Job Requirements & Responsibilities:
            {$cleanDesc}

            ═══════════════════════════════════════════════════════════════
            SEEKER DATA
            ═══════════════════════════════════════════════════════════════
            {$sourceData}

            ═══════════════════════════════════════════════════════════════
            CRITICAL FORMATTING RULES (DO NOT IGNORE)
            ═══════════════════════════════════════════════════════════════
            - DO NOT USE <table> TAGS. Ever.
            - For the Education section, put the degree, grade, and year in a single <p> tag or a clean <h3> — DO NOT create columns or tables for the dates.
            - DO NOT use <u>, <hr>, or underlined text for section headers. Use only <h2> and <h3>.
            - DO NOT repeat the candidate's name or the company name as a header at the top of the experience block. Just use the job title and dates.

            ═══════════════════════════════════════════════════════════════
            OUTPUT FORMAT & TEMPLATE
            ═══════════════════════════════════════════════════════════════
            Return HTML only. Use only these tags: div, h1, h2, h3, p, strong, em, span, ul, li. 
            Use only these classes: cv-header, cv-titles, cv-contact, entry, dates, employer, referee. 
            No other attributes, no inline styles, no markdown, no code fences.

            <div class="cv-header">
            <h1>FULL NAME IN CAPITALS</h1>
            <p class="cv-titles">Job Title | Job Title</p>
            <p class="cv-contact">City, Country | Phone | Email</p>
            </div>

            <h2>PROFESSIONAL SUMMARY</h2>
            <p>3-4 sentences summarizing experience. Highlight the 2 most important skills the job asks for that the candidate has.</p>

            <h2>CORE SKILLS</h2>
            <p><strong>Job-Relevant Skills:</strong> List the exact skills the job asks for that the candidate has (e.g., Cleaning, Record Keeping, Serving Tea, Filing).</p>

            <h2>PROFESSIONAL EXPERIENCE</h2>
            <div class="entry">
            <h3>Job Title</h3>
            <p class="dates">Start – End</p>
            <p class="employer"><em>Employer, City, Country</em></p>
            <p>Paragraph describing duties. If they did office duties (filing, cleaning, serving refreshments), say so here using the candidate's actual words.</p>
            </div>

            <h2>EDUCATION</h2>
            <div class="entry">
            <h3>Qualification (Grade)</h3>
            <p class="dates">Year</p>
            <p class="employer"><em>Institution, City, Country</em></p>
            </div>

            <h2>LANGUAGES</h2>
            <p>Language (level), Language (level)</p>

            <h2>REFEREES</h2>
            <p class="referee"><strong>Full Name</strong> | Job Title, Organisation | Phone | Email</p>

            Begin now. Output only the HTML.
            PROMPT;
    }
    
}