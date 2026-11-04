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
        protected GeminiLetterRotator $letterRotator,
    ) {}

    public function generate(LetterRequest $request): void
    {
        $startTime = microtime(true);

        $request->update(['status' => 'processing', 'error_message' => null]);

        Log::info('LetterGenerator: START', [
            'uuid'        => $request->uuid,
            'letter_type' => $request->letter_type,
            'cv_source'   => $request->cv_source,
            'cv_path'     => $request->cv_path,
        ]);

        try {
            // ── Step 1: extract CV text ─────────────────────────────
            $t = microtime(true);
            Log::info('LetterGenerator: extracting CV text', ['uuid' => $request->uuid]);

            $cvText = $this->resolveCvText($request);

            Log::info('LetterGenerator: CV extracted', [
                'uuid'  => $request->uuid,
                'chars' => strlen($cvText),
                'words' => str_word_count($cvText),
                'secs'  => round(microtime(true) - $t, 2),
            ]);

            if ($cvText === '' || !$this->isPlausibleCv($cvText)) {
                Log::warning('LetterGenerator: CV not plausible', [
                    'uuid'     => $request->uuid,
                    'chars'    => strlen($cvText),
                    'words'    => str_word_count($cvText),
                    'first_200'=> substr($cvText, 0, 200),
                ]);

                throw new \Exception(
                    'We could not read your CV. It may be a scanned image, password-protected, or not a CV. '
                    . 'Please upload a PDF or Word version of your actual CV and try again.'
                );
            }

            // ── Step 2: build job context ───────────────────────────
            $t = microtime(true);
            $jobContext = [
                'job_title'       => $request->job_title,
                'company_name'    => $request->company_name,
                'job_description' => $this->resolveJobDescription($request),
            ];

            Log::info('LetterGenerator: job context built', [
                'uuid'              => $request->uuid,
                'job_title'         => $jobContext['job_title'],
                'company_name'      => $jobContext['company_name'],
                'description_chars' => strlen((string) $jobContext['job_description']),
                'secs'              => round(microtime(true) - $t, 2),
            ]);

            // ── Step 3: call AI via the rotating Gemini pool ────────
            $t = microtime(true);
            Log::info('LetterGenerator: calling AI (rotator)', ['uuid' => $request->uuid]);

            $prompt = $this->buildLetterPromptForRotator($cvText, $jobContext, $request->letter_type);
            $raw    = $this->letterRotator->generate($prompt, 4096);
            $html   = $this->cleanLetterHtml($raw);

            $wordCount = str_word_count(strip_tags($html));
            if ($wordCount < 60) {
                throw new \Exception(
                    "AI returned too short a response ({$wordCount} words). Please try again."
                );
            }

            Log::info('LetterGenerator: AI returned', [
                'uuid'  => $request->uuid,
                'chars' => strlen($html),
                'words' => $wordCount,
                'secs'  => round(microtime(true) - $t, 2),
            ]);

            // ── Step 4: persist ─────────────────────────────────────
            $request->update([
                'content'      => $html,
                'status'       => 'generated',
                'generated_at' => now(),
            ]);

            Log::info('LetterGenerator: COMPLETE', [
                'uuid'       => $request->uuid,
                'total_secs' => round(microtime(true) - $startTime, 2),
            ]);

        } catch (\Throwable $e) {
            Log::warning('LetterGeneratorService failed', [
                'uuid'       => $request->uuid,
                'error'      => $e->getMessage(),
                'class'      => get_class($e),
                'elapsed'    => round(microtime(true) - $startTime, 2),
                'trace_head' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 5)),
            ]);

            $request->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;

        } finally {
            Log::info('LetterGenerator: cleanup', ['uuid' => $request->uuid]);

            $fresh = $request->fresh();

            if ($fresh && $fresh->status === 'generated') {
                $this->cleanupTempCv($fresh);   // ← fresh, not stale
            }
        }
    }

    protected function resolveCvText(LetterRequest $request): string
    {
        $path = $request->cv_path;

        if ($path && Storage::disk('public')->exists($path)) {
            $text = $this->tryExtract($path);
            if ($text !== '') return $text;
        }

        $seeker = $request->seekerProfile;
        if ($seeker && $seeker->cv_file_path && Storage::disk('public')->exists($seeker->cv_file_path)) {
            $text = $this->tryExtract($seeker->cv_file_path);
            if ($text !== '') return $text;
        }

        if ($seeker && is_array($seeker->cv_files)) {
            foreach ($seeker->cv_files as $entry) {
                if (!empty($entry['path']) && Storage::disk('public')->exists($entry['path'])) {
                    $text = $this->tryExtract($entry['path']);
                    if ($text !== '') return $text;
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
            if (str_contains($lower, $marker)) return true;
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

        if ($request->regeneration_count < $request->max_regenerations) {
            Log::info('LetterGenerator: keeping temp CV (regeneration budget remains)', [
                'uuid'     => $request->uuid,
                'path'     => $path,
                'attempts' => $request->regeneration_count,
                'max'      => $request->max_regenerations,
            ]);
            return;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('LetterGenerator: deleted temp CV (no regenerations left)', [
                    'uuid' => $request->uuid,
                    'path' => $path,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Letter temp CV cleanup failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Strip fences/attrs and wrap plain text into HTML.
     * (Assumed to exist elsewhere in your codebase — keep whatever you have.)
     */
    protected function cleanLetterHtml(string $raw): string
    {
        $clean = preg_replace('/^```(?:html|HTML)?\s*/', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $clean);

        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr', 'img'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        $clean = preg_replace('#<p>\s*</p>#i', '', $clean);

        if (!preg_match('/<p\b/i', $clean)) {
            $blocks = preg_split('/\n\s*\n/', $clean);
            $clean = implode('', array_map(
                fn($b) => '<p>' . nl2br(htmlspecialchars(trim($b), ENT_QUOTES)) . '</p>',
                array_filter($blocks, fn($b) => trim($b) !== '')
            ));
        }

        return trim($clean);
    }

    /**
     * Build the letter prompt for the rotating Gemini keys.
     * Same content as AiService::buildLetterPrompt() — kept here so the
     * letter flow does not depend on AiService config at all.
     */
    protected function buildLetterPromptForRotator(
        string $cvText,
        array $jobContext,
        string $letterType
    ): string {
        $jobTitle    = $jobContext['job_title']    ?? 'the advertised post';
        $companyName = $jobContext['company_name'] ?? 'the organisation';
        $jobDesc     = strip_tags($jobContext['job_description'] ?? '');
        $jobDesc     = $jobDesc !== '' ? $jobDesc : '(no detailed description provided)';

        $isApplication = $letterType === 'application';

        $subjectLine = $isApplication
            ? 'RE: APPLICATION FOR THE POST OF ' . mb_strtoupper($jobTitle)
            : 'RE: APPLICATION FOR THE POSITION OF ' . mb_strtoupper($jobTitle);

        $openingInstruction = $isApplication
            ? 'Open with a direct application statement: "I am writing to apply for the post of X as advertised in Y." State where the advert was seen.'
            : 'Open with a brief statement of interest in the position and where it was advertised. Keep it formal and direct.';

        $today = \Carbon\Carbon::now()->format('d F Y');

        return <<<PROMPT
            You are a professional career writer in Uganda. You write formal
            application letters in the East African / British Commonwealth
            style that employers in Uganda expect. NOT the American "Dear
            Hiring Manager, I'm excited to apply" style.

            TASK: Write a formal application letter for the candidate below.

            TARGET ROLE:
            Job Title: {$jobTitle}
            Company: {$companyName}
            Job Description / Context:
            {$jobDesc}

            TODAY'S DATE (use it in the letter): {$today}

            CANDIDATE CV (raw text extracted from their CV):
            ---
            {$cvText}
            ---

            STRUCTURE — follow this EXACTLY, in this order:

            ── 1. CANDIDATE HEADER (top of the letter, right side or left,
                no label needed) ──
            Line 1: the candidate's full name (from the CV, in bold)
            Line 2: their town/city and country (e.g. "Kampala, Uganda") — from the CV if present, else omit
            Line 3: their phone number and email, separated by " | " (from the CV)
            Do NOT invent a postal address or P.O. Box if the CV doesn't have one.

            ── 2. DATE (own line, right-aligned) ──
            "{$today}"

            ── 3. RECIPIENT BLOCK (left-aligned, after the date) ──
            Line 1: "The Human Resource Manager"
            Line 2: The company name exactly as provided: "{$companyName}"
            Line 3: City/location from the job context if known — omit this line if not given
            Line 4: "Uganda" (only if the previous line is present and doesn't already include "Uganda")
            Do NOT invent a P.O. Box, postal code, street address, or HR manager's personal name.

            ── 4. SUBJECT LINE (own paragraph, bold, in capitals) ──
            {$subjectLine}

            ── 5. SALUTATION ──
            "Dear Sir/Madam,"

            ── 6. BODY — four paragraphs, in this order ──
            Paragraph 1: {$openingInstruction}
            Paragraph 2: 2–3 concrete duties from the CV that match this job, with numbers if the CV gives them.
            Paragraph 3: highest qualification + 2–3 practical skills relevant to this job.
            Paragraph 4: 3 personal qualities + "I am available to start immediately and would be glad to discuss how I can support {$companyName} at an interview." + "Thank you for your time and consideration."

            ── 7. SIGN-OFF ──
            "Yours faithfully,"
            Two blank lines
            Candidate's full name in bold.

            HARD RULES:
            - 300–400 words total.
            - Formal, respectful, direct. No "thrilled", "passionate", "dynamic", no em-dashes.
            - No placeholders like [Your Name], [Company Name], [Date], [Job Title], [P.O. Box].
            - Do NOT invent employers, dates, qualifications, salary, P.O. Boxes.
            - Return clean HTML using only <p>, <strong>, <br> tags. No attributes.
            - No <html>, <head>, or <body> wrapper.
            - No markdown code fences. No commentary.

            Return the letter now.
            PROMPT;
    }


}