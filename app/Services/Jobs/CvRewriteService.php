<?php

namespace App\Services\Jobs;

use App\Models\Service\CvReviewRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Jobs\CvHtmlSanitizer;

class CvRewriteService
{
    
    
    public function __construct(
        protected GeminiLetterRotator $rotator,
        protected CvTextExtractionService $extractor,
    ) {}

    public function generate(CvReviewRequest $request): void
    {
        $start = microtime(true);

        $request->update([
            'status'                => 'generating',
            'rewrite_error_message' => null,
        ]);

        try {
            $cvText = $this->resolveCvText($request);

            if ($cvText === '') {
                throw new \Exception('Could not read the CV file for rewrite.');
            }

            $gaps = $this->formatAnswers($request);

            $prompt = $this->buildRewritePrompt(
                $cvText,
                $gaps,
                $request->target_job_title,
                $request->target_job_description
            );

            Log::info('CvRewriteService: calling AI', ['uuid' => $request->uuid]);
            $raw  = $this->rotator->generate($prompt, 6144);
            $html = CvHtmlSanitizer::clean($raw);

            $words = str_word_count(strip_tags($html));
            if ($words < 150) {
                throw new \Exception("The CV Rewriter returned too short a response ({$words} words).");
            }

            // ── Fact check: every year in the output must come from the CV or the candidate's answers
            preg_match_all('/\b(?:19|20)\d{2}\b/', $cvText, $sourceMatches);
            preg_match_all('/\b(?:19|20)\d{2}\b/', $gaps, $answerMatches);
            $allowedYears = array_merge($sourceMatches[0], $answerMatches[0]);

            preg_match_all('/\b(?:19|20)\d{2}\b/', strip_tags($html), $outputMatches);
            foreach (array_unique($outputMatches[0]) as $year) {
                if (!in_array($year, $allowedYears, true)) {
                    throw new \Exception("Generated CV contains year {$year} that is not in the source CV.");
                }
            }

            $request->update([
                'rewrite_content'          => ['html' => $html],
                'rewrite_content_original' => ['html' => $html],
                'rewrite_generated_at'     => now(),
                'status'                   => 'generated',
                'rewrite_error_message'    => null,
            ]);

            Log::info('CvRewriteService: complete', [
                'uuid' => $request->uuid,
                'secs' => round(microtime(true) - $start, 2),
                'words' => $words,
            ]);
        } catch (\Throwable $e) {
            Log::warning('CvRewriteService: failed', [
                'uuid'  => $request->uuid,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status'                => 'failed',  
                'rewrite_content'       => null,       
                'rewrite_generated_at'  => null,
                'rewrite_error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        }
    }

    public function resolveCvText(CvReviewRequest $request): string
    {
        foreach ([$request->original_cv_path, $request->seeker_cv_path] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                try {
                    $abs  = Storage::disk('public')->path($path);
                    $mime = Storage::disk('public')->mimeType($path) ?: 'application/pdf';
                    $text = trim($this->extractor->extract($abs, $mime));
                    if ($text !== '') return $text;
                } catch (\Throwable $e) {
                    Log::info('CvRewrite: extract failed', ['path' => $path]);
                }
            }
        }
        return '';
    }

    protected function formatAnswers(CvReviewRequest $r): string
    {
        $answers = $r->seeker_gap_answers ?? [];
        if (empty($answers)) return '';

        $lines = [];
        foreach ($answers as $field => $answer) {
            $label = ucwords(str_replace('_', ' ', (string) $field));
            $lines[] = "Q: {$label}\nA: {$answer}";
        }
        return implode("\n\n", $lines);
    }

    protected function buildRewritePrompt(
        string $cvText,
        string $answers,
        ?string $jobTitle,
        ?string $jobDesc
    ): string {
        $answersBlock = $answers !== ''
            ? "ADDITIONAL INFORMATION FROM THE CANDIDATE (use it to fill CV gaps, never invent beyond this):\n{$answers}"
            : 'No additional information was provided.';

        $jobBlock = '';
        if ($jobTitle || $jobDesc) {
            $cleanDesc = $jobDesc ? trim(strip_tags($jobDesc)) : '';

            $jobBlock = "\nTARGET JOB (what the CV must be tailored for):\n"
                . "Title: " . ($jobTitle ?: '-') . "\n";

            if ($cleanDesc !== '') {
                $jobBlock .= "\nFull job post (description, responsibilities, qualifications, skills):\n"
                    . $cleanDesc . "\n";
            }
        }

        return <<<PROMPT
            You are a senior CV writer in Uganda. Rewrite the CV below into the house template. The output is printed exactly as you write it, so follow the template and rules precisely.

            FACTS (most important rule)
            Use only facts that appear in the ORIGINAL CV TEXT or the ADDITIONAL INFORMATION. Do not invent or infer employers, job titles, dates, years, degrees, grades, certifications, numbers, locations, phone numbers, emails, referees, or achievements. If the CV gives no year, do not write one. Do not copy any example text from these instructions. Before you write any number, date, or name, check that it appears in the source text.

            {$answersBlock}
            {$jobBlock}

            ORIGINAL CV TEXT:
            ---
            {$cvText}
            ---

            JOB-TAILORING RULES (when a target job is given above)
            The target job block may contain a description, responsibilities, qualifications and required skills. Read all of it. Then:
            - In PROFESSIONAL SUMMARY, mention the role type and highlight 1–2 areas where the candidate's real experience matches what the job asks for.
            - In CORE SKILLS, list the skills the job asks for that the candidate genuinely has, first. Use the exact wording the candidate's CV uses for each skill — do not invent synonyms the CV doesn't use.
            - In PROFESSIONAL EXPERIENCE, where a role clearly used a skill the target job requires, state that fact in the paragraph — but only using wording that already appears in the CV or the candidate's answers.
            - Never add a skill, tool, technology, or responsibility the CV does not already mention, even if the job asks for it. The point is to *surface* matching facts, not invent them.
            - If the job asks for something the candidate lacks, quietly omit it. Do not comment on the gap.

            OUTPUT FORMAT
            Return HTML only. Use only these tags: div, h1, h2, h3, p, strong, em, span, table, tr, td. Use only these classes: cv-header, cv-titles, cv-contact, entry, entry-line, dates, employer, referee. No other attributes, no inline styles, no lists, no <br>, no markdown, no code fences, no commentary.

            TEMPLATE
            Copy this structure. Replace each placeholder with a fact from the CV. Remove any line the CV gives no facts for.

            <div class="cv-header">
            <h1>FULL NAME IN CAPITALS</h1>
            <p class="cv-titles">Job Title | Job Title</p>
            <p class="cv-contact">City, Country | Phone | Phone | Email</p>
            </div>

            <h2>PROFESSIONAL SUMMARY</h2>
            <p>Summary paragraph.</p>

            <h2>CORE SKILLS</h2>
            <p><strong>Skill Group:</strong> Skill, Skill, Skill</p>

            <h2>PROFESSIONAL EXPERIENCE</h2>
            <div class="entry">
            <table class="entry-line"><tr><td><h3>Job Title</h3></td><td class="dates">Start – End</td></tr></table>
            <p class="employer"><em>Employer, City, Country</em></p>
            <p>Paragraph describing the role.</p>
            </div>

            <h2>EDUCATION</h2>
            <div class="entry">
            <table class="entry-line"><tr><td><h3>Qualification (Grade)</h3></td><td class="dates">Year</td></tr></table>
            <p class="employer"><em>Institution, City, Country</em></p>
            </div>

            <h2>LANGUAGES</h2>
            <p>Language (level), Language (level)</p>

            <h2>REFEREES</h2>
            <p class="referee"><strong>Full Name</strong> | Job Title, Organisation | Phone | Email</p>

            HEADER RULES
            - Line 1: the candidate's full name in capitals, spelled as in the CV.
            - Line 2: the candidate's own job titles on one line, separated by " | ". Use only titles the CV uses.
            - Line 3: one line in this order: location | phone | phone | email, separated by " | ". Use only the items the CV gives. Write phone numbers as the CV writes them. Do not add labels such as "Phone:" or "Email:".
            - Nothing else goes in the header. PROFESSIONAL SUMMARY follows directly after it.

            SECTION RULES
            - Section order is fixed: header, PROFESSIONAL SUMMARY, CORE SKILLS, PROFESSIONAL EXPERIENCE, EDUCATION, LANGUAGES, REFEREES.
            - Leave out any section the CV gives no content for, except PROFESSIONAL SUMMARY and PROFESSIONAL EXPERIENCE.
            - PROFESSIONAL SUMMARY: one paragraph of 5 to 7 sentences. Open with the highest qualification and current role. Give years of experience, the scale of the current role, one measurable achievement with its number, and availability, only where the CV states them. Use personal traits only if the CV states them. No bullets.
            - CORE SKILLS: 3 to 5 lines, each label in <strong>, skills separated by commas. Do not list languages here. If a target job is given, list the most relevant skills first.
            - PROFESSIONAL EXPERIENCE: reverse chronological. Job title in <h3>. Dates in the td.dates cell, copied from the CV, with an en dash between years and "Present" for current roles. Employer line in italic: employer, city, country. Then one paragraph per role, starting with a strong action verb. Convert bullets into one paragraph. Do not repeat sentences across roles. Do not add duties the CV does not give.
            - EDUCATION: reverse chronological. Qualification and grade in <h3>. Years in the dates cell, exactly as the CV gives them. If the CV gives only an end year, write only that year. Institution in italic. Add no paragraph unless the CV gives a real detail, such as a thesis or award.
            - LANGUAGES: one <p> listing each language and its level, separated by commas. Take languages from anywhere in the CV. Keep the levels as written.
            - REFEREES: one <p class="referee"> per referee. Put everything on one line separated by " | ": name in bold, then title and organisation, then phone, then email. Leave out any part the CV does not give. Never write placeholder text such as "[to be provided]". If the CV lists no referees, omit the section.

            STYLE RULES
            - Plain English. No emojis. Do not use the words "dynamic", "passionate", "leverage", "delve", or "synergy".
            - No em dashes (—). En dashes (–) only in date ranges.
            - Target 500 to 800 words, excluding HTML tags.

            Begin now. Output only the HTML.
            PROMPT;
    }




}