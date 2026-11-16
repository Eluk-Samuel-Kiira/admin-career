<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\{ Http, Log, Cache };
use Carbon\Carbon;
use App\Models\Job\{Industry, JobCategory, JobLocation, JobType, ExperienceLevel, EducationLevel, SalaryRange};

/**
 * HOW JOB EXTRACTION WORKS NOW (line-index algorithm)
 * ---------------------------------------------------
 * 1. The advert is cleaned and split into numbered lines:  [1] text  [2] text ...
 * 2. The AI is NOT asked to retype the advert. It only returns
 *      - metadata (title, company, deadline, contacts, dropdown values), and
 *      - LINE NUMBERS for each section (description, responsibilities, ...).
 *    That reply is only a few hundred tokens, so even Cohere answers in seconds
 *    and can never "cut the advert short".
 * 3. PHP rebuilds the HTML from the original lines, word for word, with proper
 *    headings and <ul><li> lists. No paraphrasing, no AI zigzag words.
 * 4. If the AI gives bad line numbers, a PHP heading-based fallback fills the gaps.
 *
 * The CV / letter / screening methods are NOT in this file part: paste them at the
 * marker near the bottom (see the instructions in the answer).
 */
class AiJobService
{
    /** Posts under this many words are "thin": a short extra description is generated. */
    protected const THIN_POST_WORDS = 400;

    protected const GENERATED_DESC_MAX_CHARS = 500;
    protected const GENERATED_DESC_MIN_CHARS = 150;

    protected const PER_MODEL_TIMEOUT_SECONDS = 90;
    protected const CHAIN_DEADLINE_SECONDS    = 240;
    protected const MIN_ATTEMPT_SECONDS       = 15;

    protected const INDEX_MAX_TOKENS   = 3000;  // the index reply is small, so keep the cap small (faster)
    protected const MAX_SOURCE_LINES   = 450;
    protected const PROMPT_LINE_CHARS  = 700;
    protected const MIN_COVERAGE       = 0.20;  // below this share of words assigned, PHP heuristics fill the gaps

    protected array $config;
    protected array $fallbackModels;
    protected string $defaultModel;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected array $countrySettings;

    /** Apply links found in the HTML of a fetched page: [['url' => '', 'text' => ''], ...] */
    protected array $pageLinks = [];

    public function __construct()
    {
        $this->config = config('ai.models', []);
        $this->fallbackModels = config('ai.fallback_models', ['openai', 'claude', 'cohere']);
        $this->defaultModel = config('ai.default', 'gemini');
        $this->timeout = config('ai.timeout', 90);
        $this->retryAttempts = config('ai.retries.attempts', 2);
        $this->retryDelay = config('ai.retries.delay', 200);
        $this->countrySettings = config('ai.country_settings', []);
    }

    /* =====================================================================
     | Reference lists (dropdown values)
     * ===================================================================== */

    protected function getReferenceData(?string $country): array
    {
        $country = $country ? strtoupper($country) : null;

        return Cache::remember('ai_ref:' . ($country ?? 'all'), 600, function () use ($country) {
            return [
                'industries' => Industry::where('is_active', true)
                    ->orderBy('name')->pluck('name')->all(),

                'categories' => JobCategory::where('is_active', true)
                    ->when($country, fn($q) => $q->where('country_code', $country))
                    ->orderBy('name')->pluck('name')->all(),

                'locations' => JobLocation::when($country, fn($q) => $q->where('country_code', $country))
                    ->orderBy('district')->pluck('district')->unique()->values()->all(),

                'job_types' => JobType::where('is_active', true)
                    ->orderBy('name')->pluck('name')->all(),

                'experience_levels' => ExperienceLevel::where('is_active', true)
                    ->orderBy('sort_order')->pluck('name')->all(),

                'education_levels' => EducationLevel::where('is_active', true)
                    ->when($country, fn($q) => $q->where(fn($q2) => $q2->where('country_code', $country)->orWhereNull('country_code')))
                    ->orderBy('sort_order')->pluck('name')->all(),

                'salary_ranges' => SalaryRange::when($country, fn($q) => $q->where('country_code', $country))
                    ->orderBy('min_salary')->pluck('name')->all(),
            ];
        });
    }

    protected function formatReferenceListsForPrompt(array $ref): string
    {
        $line = fn(string $label, array $items) => empty($items)
            ? "{$label}: (none available)"
            : "{$label}: " . implode(' | ', $items);

        return <<<TXT
            AVAILABLE OPTIONS - for every *_name field choose an exact value from these lists (copy the text exactly).
            If nothing is a reasonable match, return null for that field.

            {$line('Industries', $ref['industries'])}
            {$line('Job Categories', $ref['categories'])}
            {$line('Locations (district)', $ref['locations'])}
            {$line('Job Types', $ref['job_types'])}
            {$line('Experience Levels', $ref['experience_levels'])}
            {$line('Education Levels', $ref['education_levels'])}
            {$line('Salary Ranges', $ref['salary_ranges'])}

            "company_name" is NOT chosen from a list: write the employer's name exactly as found.
            TXT;
    }

    /* =====================================================================
     | Model management
     * ===================================================================== */

    public function getAvailableModels(): array
    {
        $models = [];
        foreach ($this->config as $key => $model) {
            if ($this->isModelEnabled($key)) {
                $models[$key] = [
                    'name' => $model['name'] ?? ucfirst($key),
                    'icon' => $model['icon'] ?? 'ti-robot',
                    'color' => $model['color'] ?? '#6c757d',
                    'supports' => $model['supports'] ?? ['text'],
                    'is_default' => $key === $this->defaultModel,
                ];
            }
        }
        return $models;
    }

    public function isModelEnabled(string $model): bool
    {
        $config = $this->config[$model] ?? null;
        if (!$config || empty($config['api_key'])) {
            return false;
        }

        $key = (string) $config['api_key'];
        if (stripos($key, 'xxxx') !== false || stripos($key, 'your-') !== false || strlen($key) < 20) {
            return false;
        }

        return true;
    }

    protected function coolDownSeconds(string $errorMessage): int
    {
        $m = strtolower($errorMessage);

        if (preg_match('/status code (401|403)|invalid[\s_-]*(x-)?api[\s_-]*key|incorrect api key|authentication_error|unauthori[sz]ed/', $m)) {
            return 600;
        }
        if (preg_match('/status code 429|quota|rate[\s_-]*limit|resource_exhausted|too many requests/', $m)) {
            return 300;
        }
        if (preg_match('/status code 400.*api key|api key.*(invalid|incorrect)/', $m)) {
            return 600;
        }

        return 0;
    }

    protected function isCoolingDown(string $model): bool
    {
        try {
            return Cache::has("ai_model_cooldown:{$model}");
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function startCoolDown(string $model, string $errorMessage): void
    {
        $seconds = $this->coolDownSeconds($errorMessage);
        if ($seconds <= 0) {
            return;
        }
        try {
            Cache::put("ai_model_cooldown:{$model}", true, $seconds);
        } catch (\Throwable $e) {
        }
    }

    public function getModelConfig(string $model): ?array
    {
        return $this->config[$model] ?? null;
    }

    /* =====================================================================
     | JOB EXTRACTION (text / URL)
     * ===================================================================== */

    public function extractJobData(string $content, string $sourceType = 'text', ?string $model = null, ?string $country = null, ?string $position = null): array
    {
        $model     = $model ?? $this->defaultModel;
        $sourceUrl = null;
        $this->pageLinks = [];

        $this->allowLongRun(900);

        if ($sourceType === 'url') {
            $sourceUrl = $content;
            $content   = $this->fetchUrlContent($content);
        }

        // Candidate apply links are collected BEFORE cleaning, because cleaning drops bare URL lines.
        $linkCandidates = $this->collectLinkCandidates($content);

        $lines = $this->prepareLines($this->stripPageFurniture($content));
        if (!$lines) {
            throw new \Exception('No valid job posting could be found in the provided content.');
        }

        $sourceText  = implode("\n", $lines);
        $sourceWords = $this->countWords($sourceText);
        $ref         = $this->getReferenceData($country);

        $numbered = '';
        foreach ($lines as $i => $l) {
            $numbered .= '[' . ($i + 1) . '] ' . mb_substr($l, 0, self::PROMPT_LINE_CHARS) . "\n";
        }

        $prompt = $this->buildIndexPrompt($numbered, $linkCandidates, $ref, $sourceType, $position);

        $meta = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
            $raw  = $this->callAiApi($m, $key, $prompt, null, true, self::INDEX_MAX_TOKENS);
            $text = is_string($raw)
                ? trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)))
                : json_encode($raw);

            $data = $this->decodeJsonLenient($text);

            if (!is_array($data) || empty($data)) {
                throw new \Exception('Reply was not valid JSON (it may have been cut off).');
            }
            if (!empty($data['error'])) {
                return $data;
            }
            if (empty($data['job_title'])) {
                throw new \Exception('AI reply is missing job_title.');
            }

            return $data;
        }, expectJson: true);

        if (!empty($meta['error'])) {
            throw new \Exception($meta['message'] ?? 'No valid job posting could be found in the provided content.');
        }

        // 1. Body: rebuilt word for word from the original lines
        $body  = $this->assembleBody($lines, $meta);
        // 2. Apply links: chosen from real links in the source only
        $links = $this->pickApplyLinks($meta, $linkCandidates);

        // 3. Metadata + body
        $result = $meta;
        unset($result['sections'], $result['headings'], $result['apply_link_ids'], $result['skills_text']);

        $result['application_links'] = $links;
        $result['email']     = $this->keepInSource($result['email'] ?? null, $sourceText, 'email');
        $result['telephone'] = $this->keepInSource($result['telephone'] ?? null, $sourceText, 'phone');
        $result = $this->coerceModelOutput($result);

        $result['job_description']       = $body['description']      ?: null;
        $result['responsibilities']      = $body['responsibilities'] ?: null;
        $result['qualifications']        = $body['qualifications']   ?: null;
        $result['application_procedure'] = $body['application']      ?: null;

        $skills = $body['skills'];
        if ($skills === '' && !empty($meta['skills_text'])) {
            $skills = htmlspecialchars($this->normaliseSkillsLine((string) (is_array($meta['skills_text']) ? implode(', ', $meta['skills_text']) : $meta['skills_text'])), ENT_NOQUOTES, 'UTF-8');
        }
        $result['skills'] = $skills ?: null;

        // A long advert with a real description keeps exactly what the employer wrote
        $thin = $sourceWords < self::THIN_POST_WORDS;
        $result['_verbatim'] = !$thin && trim(strip_tags((string) $result['job_description'])) !== '';

        // 4. Short posts (for example WhatsApp one-liners) get a short, role-specific description
        if ($thin && str_word_count(strip_tags((string) ($result['job_description'] ?? ''))) < 120) {
            $result = $this->enrichThinPosting($result, $model, $sourceWords);
        }

        $result = $this->applySmartDefaults($result, $country, $sourceText);
        $result = $this->applyApplicationLinkHandling($result, $sourceUrl);

        return $this->applyGeorgiaFontToContentFields($result);
    }

    /* ---------------------------------------------------------------------
     | The prompt: metadata + LINE NUMBERS only
     * --------------------------------------------------------------------- */

    protected function buildIndexPrompt(string $numbered, array $links, array $ref, string $sourceType, ?string $position): string
    {
        $refBlock         = $this->formatReferenceListsForPrompt($ref);
        $humanRules       = $this->humanWritingRules();
        $fallbackDeadline = Carbon::now()->addWeeks(2)->format('Y-m-d');
        $source           = $sourceType === 'url' ? 'a job page' : 'pasted text';

        $posRule = $position !== null && trim($position) !== ''
            ? 'The advert lists several positions. Use ONLY the position titled "' . str_replace('"', '', trim($position)) . '" (match loosely).'
            : 'If the advert lists several positions, use ONLY the first one.';

        $linkBlock = '(none)';
        if ($links) {
            $rows = [];
            foreach (array_values($links) as $i => $l) {
                $rows[] = 'U' . ($i + 1) . ': ' . $l['url'] . ($l['text'] !== '' ? '   (link text: ' . $l['text'] . ')' : '');
            }
            $linkBlock = implode("\n", $rows);
        }

        return <<<PROMPT
You are an expert job-board editor for Uganda and East Africa.

The advert below ({$source}) is split into numbered lines written as [12] text.
IMPORTANT: do NOT copy or rewrite the advert. The system rebuilds the page from the line numbers you return.
Reply with ONE short JSON object only. No markdown, no code fences, no commentary.

{$posRule}

STEP 1 - SECTIONS. Give inclusive line ranges as [[first,last],[first,last]] using the numbers in brackets.
- "description": purpose / overview / summary, the role, the intro of the organisation that belongs to THIS job, and the job-details block (department, location, reports to, contract type, vacancies).
- "responsibilities": duties, responsibilities, key result areas, including their sub-headings and bullets.
- "qualifications": education, experience, knowledge and requirements, including their sub-headings and bullets.
- "skills": ONLY if the advert has its own skills / competencies section. Otherwise [].
- "application": how to apply, documents required, closing instructions for candidates.
- "headings": numbers of the lines that are sub-headings or group titles inside those ranges
  (for example "1. Partner Performance Monitoring", "Education", "Experience", "Knowledge and Skills").
  A bullet or a normal sentence is never a heading.

RULES FOR SECTIONS
- A range starts AFTER the section's own title line (do not include "Qualifications and Experience" itself, but do include sub-headings).
- Include EVERY duty and EVERY requirement of the position. Never skip lines in the middle of a list.
- A line belongs to one section only. Keep the original order.
- Leave out: menus, buttons, cookie and privacy text, sign-in, share links, equal-opportunity text, "why join us" marketing, employer boilerplate (about us), no-fee disclaimers, links to other jobs, other positions.
- A short unstructured advert (for example a WhatsApp message) has no headings: put the lines about the job in "description".

STEP 2 - METADATA
- email: only the address(es) candidates APPLY with, comma-separated, exactly as written. Never helpdesk, accessibility or technical-support addresses. null if none.
- telephone: numbers candidates can call or WhatsApp, comma-separated, exactly as written. null if none.
- is_whatsapp_contact true only if a number is described as WhatsApp. is_telephone_call true if a number is callable or has no qualifier.
- apply_link_ids: numbers (for example [1]) of at most 2 links from the list below that a candidate clicks to apply. [] if none.
- deadline: YYYY-MM-DD from the advert (convert "23 November 2026"), else "{$fallbackDeadline}".
- employment_type: full-time, part-time, contract, internship, volunteer or temporary. location_type: on-site, remote or hybrid.
- Never invent a salary. If none is stated, salary_amount, payment_period and currency are null.
- skills_text: only when "skills" is []. Write 8 to 12 specific, work-related skills for this job, 1 to 3 words each, title case, comma-separated, no generic filler such as "hard-working" or "team player". Otherwise "".
- meta_description: 120 to 158 characters, human voice, role + location + deadline.
- keywords: 8 to 12 real search phrases, comma-separated.
- other_positions: titles of the other positions in the advert, or [].
- If the text is not a real job advert (error page, login wall, unrelated text) return exactly {"error": true, "message": "No valid job posting was found in the provided content."}

{$humanRules}

APPLICATION LINK CANDIDATES (use the number only):
{$linkBlock}

{$refBlock}

OUTPUT SHAPE
{
  "job_title": "",
  "company_name": null,
  "duty_station": null,
  "deadline": "YYYY-MM-DD",
  "sections": {"description": [[1,4]], "responsibilities": [[6,20]], "qualifications": [[22,30]], "skills": [], "application": [[31,34]]},
  "headings": [6, 12, 22],
  "email": null,
  "telephone": null,
  "is_whatsapp_contact": false,
  "is_telephone_call": false,
  "apply_link_ids": [],
  "employment_type": "full-time",
  "location_type": "on-site",
  "salary_amount": null,
  "payment_period": null,
  "currency": null,
  "work_hours": null,
  "skills_text": "",
  "meta_description": "",
  "keywords": "",
  "experience_level_name": null,
  "education_level_name": null,
  "industry_name": null,
  "category_name": null,
  "job_type_name": null,
  "job_location_name": null,
  "salary_range_name": null,
  "country_code": null,
  "is_urgent": false,
  "is_cover_letter_required": false,
  "is_academic_documents_required": false,
  "is_application_required": false,
  "other_positions": []
}

ADVERT LINES
{$numbered}
PROMPT;
    }

    /* ---------------------------------------------------------------------
     | Cleaning + line preparation
     * --------------------------------------------------------------------- */

    protected function stripPageFurniture(string $text): string
    {
        $out = [];
        $furniture = '/^\s*(skip to main content\.?|view more jobs|apply now|apply to position|apply|share|print|sign in|search for jobs|introduce yourself|join (our )?talent network|careers? (home|page)|back to (search|results)|accept( cookies)?|decline|read full privacy message|read more|follow us|english|help|.*page is loaded|.*careers loaded|.*privacy (policy|notice|settings).*|©.*)\s*$/i';

        foreach (preg_split('/\R/u', $text) as $line) {
            $t = trim($line);

            if ($t === '' && ($out === [] || end($out) === '')) {
                continue;
            }
            if ($t !== '' && mb_strlen($t) <= 80 && preg_match($furniture, $t)) {
                continue;
            }
            if (preg_match('#^https?://\S+$#i', $t)) {
                continue;   // bare URL lines were already collected as link candidates
            }
            $out[] = $t;
        }

        $clean = trim(implode("\n", $out));

        return $this->countWords($clean) >= max(25, (int) ($this->countWords($text) * 0.4)) ? $clean : $text;
    }

    protected function prepareLines(string $text): array
    {
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = [];
        foreach (explode("\n", $text) as $l) {
            $l = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $l));
            if ($l !== '') {
                $lines[] = $l;
            }
        }

        return array_slice($lines, 0, self::MAX_SOURCE_LINES);
    }

    protected function countWords(string $text): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));

        return $plain === '' ? 0 : count(explode(' ', $plain));
    }

    /* ---------------------------------------------------------------------
     | Rebuilding the body from line numbers
     * --------------------------------------------------------------------- */

    protected function assembleBody(array $lines, array $meta): array
    {
        $n   = count($lines);
        $sec = is_array($meta['sections'] ?? null) ? $meta['sections'] : $meta;

        // most specific section wins if the model lets a line overlap
        $order  = ['skills', 'application', 'qualifications', 'responsibilities', 'description'];
        $taken  = [];
        $picked = [];
        foreach ($order as $name) {
            $idx = $this->parseLineRanges($sec[$name] ?? [], $n);
            $idx = array_values(array_filter($idx, fn($i) => !isset($taken[$i])));
            foreach ($idx as $i) {
                $taken[$i] = true;
            }
            $picked[$name] = $idx;
        }

        // Safety net: the model returned nothing usable, or covered too little of the advert
        $totalWords    = max(1, $this->countWords(implode(' ', $lines)));
        $assignedWords = 0;
        foreach (array_keys($taken) as $i) {
            $assignedWords += $this->countWords($lines[$i - 1]);
        }
        $core = count($picked['description']) + count($picked['responsibilities']) + count($picked['qualifications']);

        if ($core === 0 || ($assignedWords / $totalWords) < self::MIN_COVERAGE) {
            Log::info('Index extraction: weak line ranges, using PHP heading fallback', [
                'coverage' => round($assignedWords / $totalWords, 2),
            ]);
            $heur = $this->heuristicSections($lines);
            foreach ($order as $name) {
                if (!$picked[$name] && !empty($heur[$name])) {
                    $picked[$name] = $heur[$name];
                }
            }
        }

        $headings = $this->parseLineRanges($meta['headings'] ?? ($sec['headings'] ?? []), $n);
        $headSet  = array_fill_keys($headings, true);

        $skillsHtml = $this->renderSkills($picked['skills'], $lines, $headSet);

        return [
            'description'      => $this->renderSection($picked['description'], $lines, $headSet, 'description'),
            'responsibilities' => $this->renderSection($picked['responsibilities'], $lines, $headSet, 'list'),
            'qualifications'   => $this->renderSection($picked['qualifications'], $lines, $headSet, 'list'),
            'skills'           => $skillsHtml,
            'application'      => $this->renderSection($picked['application'], $lines, $headSet, 'application'),
        ];
    }

    /** Accepts [[1,5],[8,9]], [1,5], "1-5, 8", 7 ... and returns sorted unique line numbers within 1..$max. */
    protected function parseLineRanges(mixed $v, int $max): array
    {
        $out = [];
        $add = function (int $a, int $b) use (&$out, $max) {
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }
            for ($i = max(1, $a); $i <= min($max, $b); $i++) {
                $out[$i] = true;
            }
        };

        if (is_string($v)) {
            $v = preg_replace('/\bL(?=\d)/i', '', $v);
            if (preg_match_all('/(\d+)\s*(?:-|\x{2013}|\x{2014}|to)\s*(\d+)|(\d+)/iu', $v, $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    if (isset($x[3]) && $x[3] !== '') {
                        $add((int) $x[3], (int) $x[3]);
                    } else {
                        $add((int) $x[1], (int) $x[2]);
                    }
                }
            }
        } elseif (is_int($v) || is_float($v)) {
            $add((int) $v, (int) $v);
        } elseif (is_array($v) && $v) {
            $v = array_values($v);
            $allNumeric = count(array_filter($v, 'is_numeric')) === count($v);
            if ($allNumeric) {
                if (count($v) === 2 && (int) $v[1] > (int) $v[0]) {
                    $add((int) $v[0], (int) $v[1]);
                } else {
                    foreach ($v as $x) {
                        $add((int) $x, (int) $x);
                    }
                }
            } else {
                foreach ($v as $item) {
                    foreach ($this->parseLineRanges($item, $max) as $i) {
                        $out[$i] = true;
                    }
                }
            }
        }

        ksort($out);

        return array_keys($out);
    }

    protected function isSectionLabel(string $l): bool
    {
        if (count(preg_split('/\s+/', $l)) > 7) {
            return false;
        }

        return (bool) preg_match('/^(\d+[.)]\s*)?((key|main|core|job)\s+)?(job description|description|duties|responsibilities|duties and responsibilities|roles? and responsibilities|key result areas?|qualifications?( and experience)?|(minimum |required |academic |essential )?(qualifications?|requirements?)|skills?( and competenc(y|ies))?|competenc(y|ies)|how to apply|application( procedure| process)?|to apply|person specification)\s*[:.]?$/i', $l);
    }

    protected function stripBullet(string $l): string
    {
        return trim(preg_replace('/^[\x{2022}\x{25AA}\x{25CF}\x{00B7}\x{25E6}\x{2023}\x{2043}*\-\x{2013}\x{2014}]\s+/u', '', $l));
    }

    protected function hasBullet(string $l): bool
    {
        return (bool) preg_match('/^[\x{2022}\x{25AA}\x{25CF}\x{00B7}\x{25E6}\x{2023}\x{2043}*\-\x{2013}\x{2014}]\s*\S/u', $l);
    }

    protected function isNumbered(string $l): bool
    {
        return (bool) preg_match('/^\d{1,2}[.)]\s*\S/', $l);
    }

    /**
     * $type: 'description' / 'application' (paragraphs) or 'list' (every line is a list item).
     * Headings come from the model's "headings" list plus a few strong rules.
     */
    protected function renderSection(array $idx, array $lines, array $headSet, string $type): string
    {
        if (!$idx) {
            return '';
        }
        if ($this->isSectionLabel($lines[$idx[0] - 1])) {
            array_shift($idx);
        }
        if (!$idx) {
            return '';
        }

        $esc   = fn(string $s) => htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html  = '';
        $open  = false;
        $close = function () use (&$html, &$open) {
            if ($open) {
                $html .= '</ul>';
                $open = false;
            }
        };

        $count = count($idx);
        foreach ($idx as $k => $i) {
            $text = $lines[$i - 1];
            $next = $k + 1 < $count ? $lines[$idx[$k + 1] - 1] : null;

            if ($this->isHeadingLine($i, $text, $next, $headSet)) {
                $close();
                $html .= '<p><strong>' . $esc(rtrim($text, ': ')) . '</strong></p>';
                continue;
            }

            $item = $this->stripBullet($text);

            if ($type === 'list') {
                if (!$open) {
                    $html .= '<ul>';
                    $open = true;
                }
                $html .= '<li>' . $esc($item) . '</li>';
            } elseif ($this->hasBullet($text)) {
                if (!$open) {
                    $html .= '<ul>';
                    $open = true;
                }
                $html .= '<li>' . $esc($item) . '</li>';
            } else {
                $close();
                $html .= '<p>' . $esc($item) . '</p>';
            }
        }
        $close();

        return $html;
    }

    protected function isHeadingLine(int $i, string $text, ?string $next, array $headSet): bool
    {
        if (isset($headSet[$i])) {
            return true;
        }
        $words = count(preg_split('/\s+/', $text));
        if ($this->hasBullet($text) || $words > 9 || preg_match('/[.;,!?]$/', $text)) {
            return false;
        }
        // "1. Cooperating Partner Management" followed by something that is not another numbered line
        if ($this->isNumbered($text)) {
            return $next !== null && !$this->isNumbered($next);
        }
        // "Qualifications:" or "ALL CAPS TITLE"
        if (preg_match('/:$/', $text)) {
            return $words <= 8;
        }
        $letters = preg_replace('/[^A-Za-z]/', '', $text);
        if (strlen($letters) >= 4 && $letters === strtoupper($letters) && $words <= 6) {
            return true;
        }

        return false;
    }

    protected function renderSkills(array $idx, array $lines, array $headSet): string
    {
        if (!$idx) {
            return '';
        }
        if ($this->isSectionLabel($lines[$idx[0] - 1])) {
            array_shift($idx);
        }
        if (!$idx) {
            return '';
        }

        $short = true;
        foreach ($idx as $i) {
            if (isset($headSet[$i]) || count(preg_split('/\s+/', $lines[$i - 1])) > 4) {
                $short = false;
                break;
            }
        }
        if ($short) {
            $items = array_map(fn($i) => rtrim($this->stripBullet($lines[$i - 1]), '.;, '), $idx);

            return htmlspecialchars(implode(', ', $items), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->renderSection($idx, $lines, $headSet, 'list');
    }

    /** Heading-based fallback used only when the model's line ranges are missing or too thin. */
    protected function heuristicSections(array $lines): array
    {
        $respRe  = '/^(key |main |core )?(duties|responsibilities|duties and responsibilities|roles? and responsibilities|key result areas?|kras?|key performance areas?|the role|what you.?ll do|what you will do|role description|job description)\s*[:.]?$/i';
        $qualRe  = '/^(required |minimum |preferred |academic |educational |essential |desired )?(qualifications?|requirements?|qualifications? and experience|education and experience|person specification|what we.?re looking for|who you are|about you|the ideal candidate|you will need|essential criteria|skills and experience required)\s*[:.]?$/i';
        $skillRe = '/^(required |key |core |technical |professional )?(skills?|competenc(y|ies)|skills? and competencies|technical skills?)\s*[:.]?$/i';
        $applyRe = '/^(how to apply|application procedure|application process|to apply|mode of application|apply now|application instructions?)\s*[:.?]?$/i';
        $endRe   = '/^(powered by|cookie notice|share this job)/i';

        $out   = ['description' => [], 'responsibilities' => [], 'qualifications' => [], 'skills' => [], 'application' => []];
        $state = 'description';

        foreach ($lines as $k => $l) {
            $i = $k + 1;
            if (preg_match($endRe, $l)) {
                break;
            }
            if (mb_strlen($l) <= 80) {
                if (preg_match($respRe, $l))      { $state = 'responsibilities'; continue; }
                if (preg_match($qualRe, $l))      { $state = 'qualifications';   continue; }
                if (preg_match($skillRe, $l))     { $state = 'skills';           continue; }
                if (preg_match($applyRe, $l))     { $state = 'application';      continue; }
            }
            $out[$state][] = $i;
        }

        return $out;
    }

    /* ---------------------------------------------------------------------
     | Apply links + contact validation
     * --------------------------------------------------------------------- */

    protected function atsHostPattern(): string
    {
        return '/(^|\.)(workable|greenhouse|lever|bamboohr|smartrecruiters|recruitee|personio|jobvite|ashby|breezy|workday|myworkdayjobs|myworkdaysite|taleo|successfactors|icims|applytojob|oraclecloud|sapsf|eightfold|forms\.gle|docs\.google|typeform|jotform|airtable)\b/i';
    }

    protected function collectLinkCandidates(string $text): array
    {
        $found = $this->pageLinks;
        if (preg_match_all('#https?://[^\s<>"\')\]]+#i', $text, $m)) {
            foreach ($m[0] as $u) {
                $found[] = ['url' => $u, 'text' => ''];
            }
        }

        $skip = '/(^|\.)(facebook|twitter|x|instagram|youtube|tiktok|linkedin|whatsapp|t)\.(com|me)$|^wa\.me$|^bit\.ly$/i';
        $out  = [];
        $seen = [];

        foreach ($found as $f) {
            $u = rtrim(trim((string) $f['url']), ".,;:)]}>'\"");
            if (!filter_var($u, FILTER_VALIDATE_URL)) {
                continue;
            }
            $host = strtolower((string) parse_url($u, PHP_URL_HOST));
            $path = trim((string) parse_url($u, PHP_URL_PATH), '/');
            if ($host === '' || preg_match($skip, $host)) {
                continue;
            }
            if ($path === '' && !preg_match($this->atsHostPattern(), $host)) {
                continue;   // a bare homepage is never an apply link
            }
            $key = strtolower(rtrim(preg_replace('#^https?://(www\.)?#i', '', $u), '/'));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['url' => $u, 'text' => mb_substr(trim((string) ($f['text'] ?? '')), 0, 60)];

            if (count($out) >= 8) {
                break;
            }
        }

        return $out;
    }

    protected function pickApplyLinks(array $meta, array $candidates): array
    {
        $ids = $meta['apply_link_ids'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];

        $links = [];
        foreach ($ids as $id) {
            if (preg_match('/\d+/', (string) $id, $m)) {
                $c = $candidates[((int) $m[0]) - 1] ?? null;
                if ($c) {
                    $links[] = $c['url'];
                }
            }
        }

        // The model found none, but the source holds a known application-system link: use it
        if (!$links) {
            foreach ($candidates as $c) {
                if (preg_match($this->atsHostPattern(), strtolower((string) parse_url($c['url'], PHP_URL_HOST)))) {
                    $links[] = $c['url'];
                    break;
                }
            }
        }

        return array_slice(array_values(array_unique($links)), 0, 2);
    }

    /** Emails and phone numbers returned by the AI must really exist in the source text. */
    protected function keepInSource(mixed $value, string $source, string $type): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $parts = preg_split('/\s*(?:,|;|\/|\||\band\b|\bor\b)\s*/i', $value);
        $keep  = [];
        $srcDigits = preg_replace('/\D/', '', $source);

        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if ($type === 'email') {
                if (stripos($source, $p) !== false) {
                    $keep[] = $p;
                }
            } else {
                $d = preg_replace('/\D/', '', $p);
                if (strlen($d) >= 7 && str_contains($srcDigits, substr($d, -9))) {
                    $keep[] = $p;
                }
            }
        }

        return $keep ? implode(', ', array_values(array_unique($keep))) : null;
    }

    /* =====================================================================
     | Thin posts
     * ===================================================================== */

    protected function enrichThinPosting(array $data, string $model, ?int $sourceWords = null): array
    {
        $title = trim((string) ($data['job_title'] ?? ''));
        if ($title === '') {
            return $data;
        }

        $current = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($data['job_description'] ?? ''))));

        $isThin = $sourceWords !== null ? $sourceWords < self::THIN_POST_WORDS : str_word_count($current) < 120;
        if (!$isThin) {
            return $data;
        }

        $append = str_word_count($current) >= 60;

        $company  = trim((string) ($data['company_name'] ?? ''));
        $location = trim((string) ($data['duty_station'] ?? ($data['job_location_name'] ?? '')));
        $type     = (string) ($data['employment_type'] ?? '');
        $humanRules = $this->humanWritingRules();

        $facts = array_filter([
            "Job title: {$title}",
            $company  !== '' ? "Employer: {$company}" : 'Employer: not named in the post (do not invent one)',
            $location !== '' ? "Location: {$location}" : null,
            $type     !== '' ? "Employment type: {$type}" : null,
            $current  !== '' ? 'What the original post said' . ($append ? ' (shown above your text, do not repeat it)' : '') . ": {$current}" : null,
        ]);
        $factList   = implode("\n", $facts);
        $appendNote = $append
            ? 'Your text is shown AFTER the original wording, so add only what it does not already say.'
            : 'The original post has no real description, so yours is the only one the reader sees.';

        $prompt = <<<PROMPT
You write job adverts for a Ugandan and East African job board. The original post is very short, so write
a short job description a real hiring officer would have written for it.

KNOWN FACTS:
{$factList}

{$humanRules}

REQUIREMENTS:
- LENGTH IS STRICT: 350 to 500 characters in total, spaces included (about 55 to 75 words), in one or two short <p> paragraphs.
- First sentence (the one search engines quote): who is hiring (or say the employer is not named), what the job is, and where.
- Then one or two sentences on what the person does day to day and what kind of candidate suits the post. Be specific to this profession.
- {$appendNote}
- Do NOT invent the employer name, salary, benefits, deadline, number of vacancies or hard qualifications. No phone numbers, emails or links. No bullet lists.
- Return ONLY plain <p> paragraphs with no attributes. No markdown, no headings, no code fences.
PROMPT;

        try {
            $text = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
                return $this->callAiApi($m, $key, $prompt, null, false, 700);
            }, expectJson: false);

            $text = $this->cleanGeneratedHtml(is_string($text) ? $text : $this->extractTextFromResult($text));
            $text = $this->scrubAiTells($text);
            $text = $this->capGeneratedDescription($text);

            if (mb_strlen(trim(strip_tags($text))) >= self::GENERATED_DESC_MIN_CHARS) {
                $data['job_description'] = $append ? trim((string) $data['job_description']) . $text : $text;
            }
        } catch (\Throwable $e) {
            Log::warning('Thin posting enrichment failed, using static fallback', ['error' => $e->getMessage()]);
        }

        return $data;
    }

    protected function cleanGeneratedHtml(string $raw): string
    {
        $c = preg_replace('/^```(?:html)?\s*/i', '', trim($raw));
        $c = preg_replace('/\s*```\s*$/m', '', $c);
        $c = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $c);
        $c = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $t = strtolower($m[1]);
            return in_array($t, ['br', 'hr'], true) ? "<{$t} />" : "<{$t}>";
        }, $c);
        $c = trim(preg_replace('#<p>\s*</p>#i', '', $c));

        if (!preg_match('/<p\b/i', $c)) {
            $blocks = preg_split('/\n\s*\n/', $c);
            $c = implode('', array_map(
                fn($b) => '<p>' . htmlspecialchars(trim($b), ENT_NOQUOTES, 'UTF-8') . '</p>',
                array_filter($blocks, fn($b) => trim($b) !== '')
            ));
        }

        return $c;
    }

    protected function capGeneratedDescription(string $html, int $max = self::GENERATED_DESC_MAX_CHARS): string
    {
        $plainOf = fn(string $h) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (mb_strlen($plainOf($html)) <= $max) {
            return $html;
        }

        $paras = [];
        if (preg_match_all('#<p\b[^>]*>(.*?)</p>#is', $html, $m)) {
            foreach ($m[1] as $p) {
                $t = $plainOf($p);
                if ($t !== '') {
                    $paras[] = $t;
                }
            }
        }
        if (!$paras) {
            foreach (preg_split('/\n\s*\n|\n/', strip_tags($html)) as $p) {
                $t = $plainOf($p);
                if ($t !== '') {
                    $paras[] = $t;
                }
            }
        }

        $out  = [];
        $used = 0;
        $done = false;
        foreach ($paras as $para) {
            $keep = [];
            foreach (preg_split('/(?<=[.!?])\s+/u', $para) as $sentence) {
                $cost = mb_strlen($sentence) + ($keep ? 1 : ($out ? 1 : 0));
                if ($used + $cost > $max) {
                    $done = true;
                    break;
                }
                $keep[] = $sentence;
                $used += $cost;
            }
            if ($keep) {
                $out[] = implode(' ', $keep);
            }
            if ($done) {
                break;
            }
        }

        if (!$out) {
            $cut   = mb_substr($paras[0] ?? $plainOf($html), 0, $max - 1);
            $cut   = preg_replace('/\s+\S*$/u', '', $cut);
            $out[] = rtrim($cut, ' ,;:-') . '.';
        }

        return implode('', array_map(fn($t) => '<p>' . htmlspecialchars($t, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>', $out));
    }

    /* =====================================================================
     | URL fetching
     * ===================================================================== */

    protected function fetchUrlContent(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('The provided URL is not valid.');
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; JobBoardBot/1.0)',
            ])->timeout(20)->get($url);
        } catch (\Exception $e) {
            throw new \Exception('This job posting does not exist - the URL could not be reached: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new \Exception("This job posting does not exist - the page returned HTTP {$response->status()}.");
        }

        $html = $response->body();

        // Keep the apply buttons: their href disappears when tags are stripped
        $this->pageLinks = [];
        $base = parse_url($url);
        $origin = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '');
        if (preg_match_all('#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $a) {
                $href = html_entity_decode(trim($a[1]), ENT_QUOTES | ENT_HTML5);
                $txt  = trim(preg_replace('/\s+/', ' ', strip_tags($a[2])));
                if (str_starts_with($href, '/')) {
                    $href = $origin . $href;
                }
                $host = strtolower((string) parse_url($href, PHP_URL_HOST));
                if (preg_match('/\b(apply|application|submit|register)\b/i', $txt) || ($host && preg_match($this->atsHostPattern(), $host))) {
                    $this->pageLinks[] = ['url' => $href, 'text' => $txt];
                }
            }
        }

        $text = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $text = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $text);
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = preg_replace('#</(p|div|li|ul|ol|h[1-6]|tr|section|article|header|footer)>#i', "\n", $text);
        $text = preg_replace('#<li\b[^>]*>#i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = preg_replace('/\s*\n\s*/', "\n", $text);
        $text = trim($text);

        if (mb_strlen($text) < 200) {
            throw new \Exception('This job posting does not exist - no readable job content was found at that URL.');
        }

        return mb_substr($text, 0, 18000);
    }

    /* =====================================================================
     | Image extraction (the model must read the picture, so it returns the text)
     * ===================================================================== */

    public function extractFromImage(string $imageBase64, ?string $model = null, ?string $country = null): array
    {
        $model = $model ?? $this->defaultModel;

        if (!$this->isModelEnabled($model)) {
            throw new \Exception("Model '{$model}' is not enabled or configured.");
        }

        $config = $this->getModelConfig($model);
        if (!in_array('image', $config['supports'] ?? [])) {
            throw new \Exception("Model '{$model}' does not support image processing.");
        }

        $this->allowLongRun(300);

        $referenceData = $this->getReferenceData($country);
        $prompt = $this->buildImageExtractionPrompt($referenceData);

        $result = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt, $imageBase64) {
            return $this->callAiApi($m, $key, $prompt, $imageBase64, true);
        }, expectJson: true);

        if (!empty($result['error'])) {
            throw new \Exception($result['message'] ?? 'No job details could be read from the image.');
        }

        $result = $this->coerceModelOutput($result);
        $result = $this->enrichThinPosting($result, $model);
        $result = $this->applySmartDefaults($result, $country, '');
        $result = $this->applyApplicationLinkHandling($result);

        return $this->applyGeorgiaFontToContentFields($result);
    }

    protected function buildImageExtractionPrompt(array $referenceData = []): string
    {
        $referenceBlock = $this->formatReferenceListsForPrompt($referenceData);

        return "Extract all job information visible in this image. Preserve the EXACT text as shown - "
            . "do not summarize or reword anything that is visible. Only generate content for fields that are completely absent.\n\n"
            . "{$referenceBlock}\n\n"
            . "RESPONSIBILITIES AND QUALIFICATIONS MUST BE HTML LISTS using <ul><li>...</li></ul>, never plain text with dashes or commas.\n\n"
            . "Do NOT put emails, phone numbers, WhatsApp numbers or links inside job_description, responsibilities, qualifications or skills. "
            . "Put them only in email / telephone / application_links. Several numbers go in telephone separated by commas (never \"/\", \"and\", \"or\"). "
            . "Every application link (http or https) visible in the image goes in application_links as an array of full URLs. Emails and phone numbers are never links. "
            . "Do not write any warning about paying money: one is added automatically.\n\n"
            . $this->humanWritingRules() . "\n\n"
            . "Never lengthen or pad text. If the image has no real description of the job, return null for job_description. "
            . "Return ONLY one valid JSON object with these fields: job_title, company_name, job_description, responsibilities, qualifications, skills, "
            . "application_procedure, application_links, email, telephone, is_whatsapp_contact, is_telephone_call, deadline (YYYY-MM-DD or null), duty_station, "
            . "location_type, employment_type, salary_amount, payment_period, currency, work_hours, meta_description, keywords, experience_level_name, "
            . "education_level_name, industry_name, category_name, job_type_name, job_location_name, salary_range_name, country_code, is_urgent, "
            . "is_cover_letter_required, is_academic_documents_required, is_application_required. "
            . "company_name is written exactly as shown; every other *_name field must be an exact value from the lists above, else null. "
            . "No HTML attributes (no style, no class). If the image does not show a real job posting return "
            . "{\"error\": true, \"message\": \"No job posting could be read from this image.\"}";
    }

    /* =====================================================================
     | Apply link + contact handling
     * ===================================================================== */

    protected function applyApplicationLinkHandling(array $data, ?string $sourceUrl = null): array
    {
        $bodyFields = ['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'];

        $links = [];
        foreach (['application_links', 'application_link'] as $f) {
            if (!empty($data[$f])) {
                $links = array_merge($links, (array) $data[$f]);
            }
        }
        foreach ($bodyFields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $links = array_merge($links, $this->findLinks($data[$field]));
            }
        }
        if (!$links && $sourceUrl && filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            $links[] = $sourceUrl;
        }
        $links = $this->cleanLinkList($links);

        foreach ($bodyFields as $field) {
            if (empty($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            $clean = preg_replace('#<a\b[^>]*>(.*?)</a>#is', '$1', $data[$field]);
            $clean = preg_replace('#https?://[^\s<>"\']+#i', '', $clean);
            $clean = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '', $clean);
            $clean = preg_replace('/(?<![\d,.])(?:\+?256[\s-]?|0)7\d{2}[\s-]?\d{3}[\s-]?\d{3}(?![\d,])/', '', $clean);
            $clean = preg_replace('#<(p|li)\b[^>]*>\s*(?:[:\-\x{2013}]\s*)?</\1>#iu', '', $clean);
            $data[$field] = $clean;
        }

        if ($links) {
            $existing = (string) ($data['application_procedure'] ?? '');
            $linkHtml = $this->buildApplyLinkHtml($links);
            $data['application_procedure'] = trim(strip_tags($existing)) !== '' ? $existing . $linkHtml : $linkHtml;
            $data['is_application_required'] = true;
        }

        if (!empty($data['telephone']) && is_string($data['telephone'])) {
            $data['telephone'] = $this->normalizeTelephone($data['telephone']);
        }
        if (!empty($data['email']) && is_string($data['email'])) {
            $emails = preg_split('/\s*(?:,|;|\/|\||\band\b|\bor\b)\s*/i', $data['email']);
            $data['email'] = implode(', ', array_values(array_unique(array_filter(array_map('trim', $emails)))));
        }

        $data = $this->applyPaymentWarning($data);

        unset($data['application_link'], $data['application_links']);

        return $data;
    }

    protected function findLinks(string $text): array
    {
        $found = [];
        if (preg_match_all('#href\s*=\s*["\']([^"\']+)["\']#i', $text, $m)) {
            $found = array_merge($found, $m[1]);
        }
        $plain = preg_replace('#<a\b[^>]*>.*?</a>#is', ' ', $text);
        if (preg_match_all('#https?://[^\s<>"\']+#i', $plain, $m)) {
            $found = array_merge($found, $m[0]);
        }

        return $found;
    }

    protected function cleanLinkList(array $links): array
    {
        $skipHosts = '/(^|\.)(facebook|twitter|x|instagram|youtube|tiktok|whatsapp|t)\.(com|me)$|^wa\.me$|^bit\.ly$/i';
        $out  = [];
        $seen = [];

        foreach ($links as $link) {
            $link = rtrim(trim((string) $link), ".,;:)]}>'\"");
            if (!preg_match('#^https?://#i', $link) || !filter_var($link, FILTER_VALIDATE_URL)) {
                continue;
            }
            $host = strtolower((string) parse_url($link, PHP_URL_HOST));
            if ($host === '' || preg_match($skipHosts, $host)) {
                continue;
            }
            $key = strtolower(rtrim(preg_replace('#^https?://(www\.)?#i', '', $link), '/'));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $link;
        }

        return $out;
    }

    protected function buildApplyLinkHtml(array $links): string
    {
        $html = '';
        foreach (array_values($links) as $i => $link) {
            $safe  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $label = $i === 0 ? 'click here' : 'click here (link ' . ($i + 1) . ')';
            $html .= '<p>To apply for this role or job <a href="' . $safe . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></p>';
        }

        return $html;
    }

    protected function paymentWarningVariants(): array
    {
        return [
            'DO NOT PAY ANY MONEY FOR THIS JOB ADVERT OR ANY PART OF THE RECRUITMENT PROCESS.',
            'NEVER PAY ANYONE TO GET THIS JOB. RECRUITMENT FOR THIS POSITION IS FREE.',
            'BEWARE OF FAKE RECRUITERS. NO GENUINE EMPLOYER ASKS FOR MONEY TO APPLY, TO BE INTERVIEWED OR TO BE HIRED.',
            'THIS ADVERT AND THE WHOLE HIRING PROCESS ARE FREE OF CHARGE. PLEASE DO NOT SEND MONEY TO ANYONE.',
            'IF ANYONE ASKS YOU TO PAY FOR THIS JOB, FOR AN INTERVIEW OR FOR "PROCESSING", DO NOT PAY. IT IS A SCAM.',
            'A WORD OF CAUTION: DO NOT PAY ANY FEE TO APPLY FOR THIS JOB OR TO BE CONSIDERED FOR IT.',
            'NO PAYMENT IS REQUIRED AT ANY STAGE OF THIS RECRUITMENT. DO NOT PAY ANYONE FOR THIS JOB.',
            'PLEASE NOTE: YOU SHOULD NEVER PAY MONEY FOR A JOB ADVERT OR FOR ANY RECRUITMENT STEP. KEEP YOUR MONEY.',
            'APPLICATION IS FREE. DO NOT PAY FOR FORMS, INTERVIEWS, MEDICALS, UNIFORMS OR ANY OTHER "REQUIREMENT" TO GET THIS JOB.',
            'STAY SAFE: DO NOT PAY MONEY FOR THIS JOB ADVERT OR ANY RECRUITMENT PROCESS, NOT EVEN A SMALL AMOUNT.',
            'BE WARNED: ANYONE COLLECTING MONEY FROM JOB SEEKERS FOR THIS VACANCY IS NOT TO BE TRUSTED. DO NOT PAY.',
            'REMEMBER, YOU DO NOT PAY TO GET A JOB. DO NOT SEND MONEY FOR THIS ADVERT OR ANY RECRUITMENT PROCESS.',
            'DO NOT BE CONNED. NOBODY SHOULD CHARGE YOU ANYTHING FOR THIS VACANCY OR ITS RECRUITMENT PROCESS.',
            'DO NOT PAY MONEY TO ANYONE CLAIMING THEY CAN GET YOU THIS JOB OR HELP YOU PASS THE RECRUITMENT PROCESS.',
        ];
    }

    protected function applyPaymentWarning(array $data): array
    {
        $procedure = (string) ($data['application_procedure'] ?? '');
        $plain     = trim(strip_tags($procedure));

        if ($plain !== '' && preg_match('/\b(DO NOT|DON\'T|NEVER|NO)\W+(?:\w+\W+){0,4}(PAY|PAYMENT|FEE|FEES|MONEY)\b/', $plain)) {
            $data['application_procedure'] = $procedure;
            return $data;
        }

        $seedBase = ($data['job_title'] ?? '') . '|' . ($data['company_name'] ?? '') . '|' . ($data['deadline'] ?? '');

        if ($plain === '') {
            $procedure = $this->generateFallbackApplicationProcedure($seedBase, !empty($data['email']), !empty($data['telephone']));
        }

        $variants = $this->paymentWarningVariants();
        $text     = $variants[crc32($seedBase . 'w') % count($variants)];
        $style    = crc32($seedBase . 's') % 3;
        $before   = crc32($seedBase . 'p') % 4 === 0;

        $notice = match ($style) {
            0       => '<p><strong>' . $text . '</strong></p>',
            1       => '<p><strong>NOTICE:</strong> ' . $text . '</p>',
            default => '<p>' . $text . '</p>',
        };

        $data['application_procedure'] = $before ? $notice . $procedure : $procedure . $notice;

        return $data;
    }

    protected function generateFallbackApplicationProcedure(string $seed, bool $hasEmail, bool $hasPhone): string
    {
        $contact = match (true) {
            $hasEmail && $hasPhone => ['using the email address or phone number shown on this page', 'through the email or phone contact on this page'],
            $hasEmail              => ['using the email address shown on this page', 'to the email address on this page'],
            $hasPhone              => ['using the phone number shown on this page', 'on the phone number given on this page'],
            default                => ['using the details on this page', 'through the contact details on this page'],
        };

        $variants = [
            "<p>Interested candidates should send an updated CV and a short cover letter {$contact[0]}, before the closing date.</p>",
            "<p>If you qualify, send your CV and cover letter {$contact[1]}. Applications received after the deadline will not be looked at.</p>",
            "<p>Send your CV, a cover letter and copies of your academic documents {$contact[0]}. Only shortlisted candidates will be contacted.</p>",
            "<p>To apply, get in touch {$contact[1]} with your CV attached. Please mention the job title in your message.</p>",
        ];

        return $variants[crc32($seed . 'a') % count($variants)];
    }

    /* =====================================================================
     | Writing rules for text WE ask the AI to write
     * ===================================================================== */

    protected function humanWritingRules(): string
    {
        return <<<RULES
HUMAN WRITING RULES (anything you write yourself, not text copied from the source):
- Write the way a hiring officer in East Africa would type a real advert: plain, direct, specific.
- Never use: delve, leverage, utilize, synergy, dynamic, passionate, vibrant, fast-paced, thrive, seamless, robust,
  cutting-edge, state-of-the-art, game-changer, pivotal, tapestry, landscape, realm, "in today's", "join our team",
  "exciting opportunity", "rewarding career", "a proven track record", "results-driven", "self-starter", "plays a crucial role".
- No em dashes or en dashes. No emojis, no rhetorical questions, no closing sentence that sums up the advert.
- Vary sentence length. Prefer concrete things (what the person does, uses, reports to) over adjectives.
- Do not repeat the job title in every sentence and do not stuff keywords.
RULES;
    }

    protected function scrubAiTells(string $text): string
    {
        $text = preg_replace('/\s*[\x{2014}\x{2013}]\s*/u', ', ', $text);
        $text = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"], ["'", "'", '"', '"'], $text);
        $text = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);

        $swap = [
            '/\bleverag(e|es|ed|ing)\b/i'   => 'use',
            '/\butili[sz](e|es|ed|ing)\b/i' => 'use',
            '/\bdelv(e|es|ed|ing) into\b/i' => 'look into',
            '/\bseamless(ly)?\b/i'          => 'smooth',
            '/\bcutting-edge\b/i'           => 'modern',
            '/\bfast-paced\b/i'             => 'busy',
            '/\bdynamic\b/i'                => 'active',
            '/\bpassionate\b/i'             => 'committed',
            '/\bvibrant\b/i'                => 'lively',
            '/\bpivotal\b/i'                => 'important',
            '/\bresults-driven\b/i'         => 'focused',
        ];
        $text = preg_replace(array_keys($swap), array_values($swap), $text);

        return preg_replace('/,\s*,/', ',', $text);
    }

    protected function scrubGeneratedFields(array $data): array
    {
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills', 'meta_description', 'keywords'] as $f) {
            if (!empty($data[$f]) && is_string($data[$f])) {
                $data[$f] = $this->scrubAiTells($data[$f]);
            }
        }
        return $data;
    }

    protected function seededPick(array $pool, string $seed, int $count = 1): array
    {
        $scored = [];
        foreach ($pool as $i => $item) {
            $scored[] = [crc32($seed . '#' . $i), $item];
        }
        usort($scored, fn($a, $b) => $a[0] <=> $b[0]);

        return array_map(fn($s) => $s[1], array_slice($scored, 0, $count));
    }

    protected function normalizeTelephone(string $telephone): string
    {
        $normalized = preg_replace('/\s*(\/|;|\band\b|\bor\b|\|)\s*/i', ', ', $telephone);
        $numbers = array_filter(array_map('trim', explode(',', $normalized)), fn($n) => $n !== '');

        return implode(', ', array_values(array_unique($numbers)));
    }

    protected function normaliseSkillsLine(string $raw): string
    {
        $raw = trim(preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($raw)));
        if (preg_match('/\[(.*)\]/s', $raw, $m)) {
            $raw = $m[1];
        }

        $seen = [];
        $out  = [];
        foreach (preg_split('/\r?\n|,|;/', $raw) as $p) {
            $p = preg_replace('/^\s*[\-\*\x{2022}\d.)]+\s*/u', '', $p);
            $p = trim($p, " \t\n\r\0\x0B\"'.,;:");
            if ($p === '' || mb_strlen($p) > 40 || substr_count($p, ' ') > 5) {
                continue;
            }
            $key = mb_strtolower($p);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $p;
        }

        return implode(', ', array_slice($out, 0, 12));
    }

    /* =====================================================================
     | Field enhance + generate from title
     * ===================================================================== */

    public function enhanceField(string $fieldName, string $content, string $instruction, ?string $model = null): string
    {
        $model  = $model ?? $this->defaultModel;
        $prompt = $this->buildEnhancePrompt($fieldName, $content, $instruction);

        $text = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
            return $this->callAiApi($m, $key, $prompt);
        }, expectJson: false);

        $text = is_string($text) ? trim($text) : $this->extractTextFromResult($text);

        return $this->scrubAiTells($text);
    }

    public function generateFromTitle(string $title, ?string $company = null, ?string $country = null, ?string $model = null): array
    {
        $model = $model ?? $this->defaultModel;
        $referenceData = $this->getReferenceData($country);
        $prompt = $this->buildGeneratePrompt($title, $company, $country, $referenceData);

        $result = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
            return $this->callAiApi($m, $key, $prompt, null, true);
        }, expectJson: true);

        $result = $this->coerceModelOutput($result);
        $result = $this->scrubGeneratedFields($result);
        if (!empty($result['job_description']) && is_string($result['job_description'])) {
            $result['job_description'] = $this->capGeneratedDescription($result['job_description']);
        }
        $result['job_title']    = $result['job_title'] ?? $title;
        $result['company_name'] = $result['company_name'] ?? ($company ?? '');
        $result = $this->applySmartDefaults($result, $country, '');
        $result = $this->applyApplicationLinkHandling($result);

        return $this->applyGeorgiaFontToContentFields($result);
    }

    protected function buildGeneratePrompt(string $title, ?string $company, ?string $country, array $referenceData = []): string
    {
        $companyText = $company ? " at {$company}" : '';
        $countryText = $country ? " in {$country}" : ' in East Africa';
        $deadline = Carbon::now()->addWeeks(2)->format('Y-m-d');
        $referenceBlock = $this->formatReferenceListsForPrompt($referenceData);
        $humanRules = $this->humanWritingRules();

        return <<<PROMPT
Generate a complete, professional job posting for a "{$title}"{$companyText}{$countryText}.

Responsibilities and qualifications MUST be HTML unordered lists using <ul> and <li>, never plain text with dashes or commas.
Example: "<ul><li>Lead and manage key initiatives</li><li>Prepare weekly reports</li></ul>"

{$referenceBlock}

{$humanRules}

Include the job title and relevant industry/skill/location keywords once or twice, never forced. The job_description is STRICTLY
350 to 500 characters in total, spaces included (55 to 75 words), in one or two short paragraphs.

Return ONLY a valid JSON object, no markdown or code blocks. No HTML attributes on any tag. No contact info or links anywhere.

{
"job_description": "one or two short <p> paragraphs, 350 to 500 characters in total",
"responsibilities": "<ul><li>...</li></ul>",
"qualifications": "<ul><li>...</li></ul>",
"skills": "comma-separated list of 6-10 skills specific to this job",
"meta_description": "155-character SEO meta description",
"keywords": "comma-separated SEO keywords",
"experience_level_name": "EXACT value from the Experience Levels list, else null",
"education_level_name": "EXACT value from the Education Levels list, else null",
"industry_name": "EXACT value from the Industries list, else null",
"category_name": "EXACT value from the Job Categories list, else null",
"job_type_name": "EXACT value from the Job Types list, else null",
"employment_type": "full-time|part-time|contract|internship|volunteer|temporary",
"location_type": "on-site|remote|hybrid",
"deadline": "{$deadline}"
}
PROMPT;
    }

    protected function buildEnhancePrompt(string $fieldName, string $content, string $instruction): string
    {
        $fieldLabel = ucwords(str_replace('_', ' ', $fieldName));
        $humanRules = $this->humanWritingRules();

        return <<<PROMPT
You are an expert HR copywriter. Your task: {$instruction}

FIELD BEING IMPROVED: {$fieldLabel}

RULES:
- Preserve the original meaning and every fact; improve clarity and professionalism only.
- {$humanRules}
- Descriptions and procedures read as flowing paragraphs; responsibilities and qualifications read as crisp lists.
- Return ONLY the improved content as clean HTML using <p> and <ul><li>, no attributes on any tag.
- No explanations, no markdown fences, no JSON.

CURRENT CONTENT:
{$content}
PROMPT;
    }

    /* =====================================================================
     | Calling the models
     * ===================================================================== */

    protected function callWithFallback(string $primaryModel, string $prompt, callable $callFn, bool $expectJson = true): array|string
    {
        $errors = [];

        $this->allowLongRun(300);

        $deadline = microtime(true) + self::CHAIN_DEADLINE_SECONDS;

        $models = array_values(array_unique(array_merge(
            [$primaryModel],
            ['gemini', 'claude', 'openai'],
            ['grok', 'mistral'],
            ['cohere'],
        )));

        foreach ($models as $model) {
            if (!$this->isModelEnabled($model)) {
                $errors[$model] = 'not enabled (missing or placeholder API key)';
                continue;
            }
            if ($this->isCoolingDown($model)) {
                $errors[$model] = 'skipped for a few minutes after its last quota/API-key error';
                continue;
            }

            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining < self::MIN_ATTEMPT_SECONDS) {
                $errors[$model] = 'skipped: the time limit for this request was reached';
                continue;
            }
            $this->timeout = min(self::PER_MODEL_TIMEOUT_SECONDS, $remaining);

            $t0 = microtime(true);

            try {
                $config = $this->getModelConfig($model);
                $raw    = $callFn($model, $config['api_key']);

                Log::info("AI call '{$model}' succeeded in " . round(microtime(true) - $t0, 1) . 's');

                if (!$expectJson) {
                    return is_array($raw) ? $this->extractTextFromResult($raw) : (string) $raw;
                }

                return $this->parseJsonOrFail($raw, $model);
            } catch (\Throwable $e) {
                $errorMessage   = $e->getMessage();
                $cleanError     = preg_replace('/^API error \([^)]+\):\s*/', '', $errorMessage);
                $errors[$model] = $cleanError ?: $errorMessage;
                $this->startCoolDown($model, $errorMessage);

                Log::warning("AI call failed for model '{$model}' after " . round(microtime(true) - $t0, 1) . 's', [
                    'error' => mb_substr($errorMessage, 0, 300),
                ]);
            }
        }

        throw new \Exception(json_encode([
            'type'   => 'model_errors',
            'errors' => $errors,
        ]));
    }

    protected function callAiApi(string $model, string $apiKey, string $prompt, ?string $imageBase64 = null, bool $json = false, ?int $maxTokens = null): array|string
    {
        $config = $this->getModelConfig($model);
        if (!$config) {
            throw new \Exception("Model '{$model}' not configured.");
        }

        $endpoint  = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $maxTokens ?? 8192;

        $headers = ['Content-Type' => 'application/json'];

        if (in_array($model, ['openai', 'grok', 'mistral', 'cohere'], true)) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key']         = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }

        $body = $this->buildRequestBody($model, $modelName, $maxTokens, $prompt, $imageBase64, $json);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        $response = Http::timeout($this->timeout)
            ->retry(2, 1500, function ($exception) {
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return !str_contains(strtolower($exception->getMessage()), 'timed out');
                }
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    return in_array($exception->response->status(), [500, 502, 503, 504], true);
                }
                return false;
            })
            ->withHeaders($headers)
            ->post($endpoint, $body);

        if (!$response->successful()) {
            Log::warning(strtoupper($model) . ' RESPONSE (failed)', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 400),
            ]);
            throw $this->buildApiException($model, $response);
        }

        $data = $response->json();
        $this->assertNotTruncated($model, is_array($data) ? $data : []);

        return $this->extractResponseText($model, is_array($data) ? $data : []);
    }

    /** A queue worker is one long-lived process: never leave a time limit running after the job ends. */
    protected function allowLongRun(int $seconds): void
    {
        @set_time_limit(app()->runningInConsole() ? 0 : $seconds);
    }

    protected function assertNotTruncated(string $model, array $data): void
    {
        $reason = match ($model) {
            'gemini'                    => $data['candidates'][0]['finishReason'] ?? null,
            'cohere'                    => $data['finish_reason'] ?? null,
            'openai', 'grok', 'mistral' => $data['choices'][0]['finish_reason'] ?? null,
            'claude'                    => $data['stop_reason'] ?? null,
            default                     => null,
        };

        if (in_array(strtoupper((string) $reason), ['MAX_TOKENS', 'LENGTH'], true)) {
            throw new \Exception("Reply cut short by the output limit ({$model} stopped at {$reason}).");
        }
    }

    private function buildRequestBody(string $model, string $modelName, int $maxTokens, string $prompt, ?string $imageBase64, bool $json = false): array
    {
        switch ($model) {
            case 'openai':
                $body = [
                    'model'      => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages'   => [[
                        'role'    => 'user',
                        'content' => $imageBase64
                            ? [
                                ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                                ['type' => 'text', 'text' => $prompt],
                            ]
                            : $prompt,
                    ]],
                ];
                if ($json) {
                    $body['temperature'] = 0;
                    $body['response_format'] = ['type' => 'json_object'];
                }
                return $body;

            case 'claude':
                $content = $imageBase64
                    ? [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['type' => 'text', 'text' => $prompt],
                    ]
                    : $prompt;

                return [
                    'model'      => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $content]],
                ];

            case 'gemini':
                $parts = $imageBase64
                    ? [
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['text' => $prompt],
                    ]
                    : [['text' => $prompt]];

                $generationConfig = ['maxOutputTokens' => $maxTokens];
                if ($json) {
                    $generationConfig['responseMimeType'] = 'application/json';
                    $generationConfig['temperature'] = 0;
                }
                if (str_contains($modelName, '2.5-flash')) {
                    $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
                } elseif (str_contains($modelName, 'gemini-3')) {
                    $generationConfig['thinkingConfig'] = ['thinkingLevel' => 'minimal'];
                }

                return [
                    'contents'         => [['parts' => $parts]],
                    'generationConfig' => $generationConfig,
                ];

            case 'grok':
            case 'cohere':
            case 'mistral':
                $body = [
                    'model'      => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ];
                if ($json) {
                    $body['temperature'] = 0;
                    if ($model !== 'grok') {
                        $body['response_format'] = ['type' => 'json_object'];
                    }
                }
                return $body;

            default:
                throw new \Exception("Unsupported model: {$model}");
        }
    }

    private function buildApiException(string $model, $response): \Exception
    {
        $errorBody = $response->body();
        $errorData = json_decode($errorBody, true);

        return new \Exception("API error ({$model}): " . $this->extractErrorMessage(is_array($errorData) ? $errorData : null, $errorBody));
    }

    private function extractErrorMessage(?array $errorData, string $fallback): string
    {
        if (!is_array($errorData)) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($fallback)));
            return $plain !== '' ? mb_substr($plain, 0, 300) : 'Empty or non-JSON error response from the provider.';
        }

        foreach (['error.message', 'error.error.message', 'error.error', 'message', 'error'] as $path) {
            $value = $this->getNestedValue($errorData, $path);
            if ($value && is_string($value)) {
                return $value;
            }
        }

        return preg_replace('/\s+/', ' ', trim($fallback));
    }

    private function extractResponseText(string $model, array $data): string
    {
        $text = match ($model) {
            'openai', 'grok', 'mistral' => $data['choices'][0]['message']['content'] ?? '',
            'claude' => $data['content'][0]['text'] ?? '',
            'gemini' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'cohere' => $data['message']['content'][0]['text'] ?? $data['text'] ?? $data['response'] ?? $data['generation'] ?? '',
            default  => json_encode($data),
        };

        if (empty($text)) {
            Log::warning("Empty response from AI model '{$model}'", ['full_response' => mb_substr(json_encode($data), 0, 500)]);
        }

        return (string) $text;
    }

    protected function extractTextFromResult(mixed $result): string
    {
        if (is_string($result)) {
            return $result;
        }
        if (is_array($result)) {
            foreach (['text', 'content', 'enhanced', 'job_description'] as $key) {
                if (isset($result[$key]) && is_string($result[$key])) {
                    return $result[$key];
                }
            }
            $first = reset($result);
            return is_string($first) ? $first : '';
        }

        return (string) $result;
    }

    /* =====================================================================
     | JSON handling
     * ===================================================================== */

    protected function coerceModelOutput(array $d): array
    {
        $flat = function ($v) use (&$flat): array {
            if (is_array($v)) {
                $out = [];
                foreach ($v as $item) {
                    $out = array_merge($out, $flat($item));
                }
                return $out;
            }
            if ($v === null || is_bool($v)) {
                return [];
            }
            $t = trim((string) $v);
            return $t === '' ? [] : [$t];
        };

        foreach (['responsibilities', 'qualifications'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $items = $flat($d[$f]);
                $d[$f] = $items ? '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>' : null;
            }
        }
        foreach (['job_description', 'application_procedure'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $items = $flat($d[$f]);
                $d[$f] = $items ? '<p>' . implode('</p><p>', $items) . '</p>' : null;
            }
        }
        if (isset($d['other_positions'])) {
            $d['other_positions'] = is_array($d['other_positions']) ? $flat($d['other_positions']) : [];
        }
        foreach (['skills', 'keywords', 'email', 'telephone'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $d[$f] = implode(', ', $flat($d[$f])) ?: null;
            }
        }
        foreach ([
            'job_title', 'company_name', 'duty_station', 'meta_description', 'deadline',
            'location_type', 'employment_type', 'salary_amount', 'payment_period', 'currency', 'work_hours',
            'experience_level_name', 'education_level_name', 'industry_name', 'category_name',
            'job_type_name', 'job_location_name', 'salary_range_name', 'country_code',
        ] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $d[$f] = implode(' ', $flat($d[$f])) ?: null;
            } elseif (isset($d[$f]) && !is_string($d[$f]) && !is_null($d[$f])) {
                $d[$f] = (string) $d[$f];
            }
        }

        $links = [];
        foreach (['application_links', 'application_link'] as $f) {
            if (isset($d[$f])) {
                $links = array_merge($links, is_array($d[$f]) ? $flat($d[$f]) : [(string) $d[$f]]);
            }
        }
        $d['application_links'] = $links;
        unset($d['application_link']);

        return $d;
    }

    protected function parseJsonOrFail(mixed $result, string $model): array
    {
        if (is_array($result) && !empty($result)) {
            return $result;
        }

        if (is_string($result)) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $result);
            $clean = trim(preg_replace('/\s*```\s*$/i', '', $clean));

            $decoded = $this->decodeJsonLenient($clean);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }

            $opens  = substr_count($clean, '{') + substr_count($clean, '[');
            $closes = substr_count($clean, '}') + substr_count($clean, ']');
            $why = $opens > $closes
                ? 'the reply was cut off (output limit reached), ' . mb_strlen($clean) . ' characters received'
                : 'the reply is not valid JSON';
            throw new \Exception("Response could not be parsed as JSON: {$why}. Start: "
                . substr($clean, 0, 200) . ' ... End: ' . substr($clean, -200));
        }

        throw new \Exception('Empty or invalid response.');
    }

    protected function decodeJsonLenient(string $text): ?array
    {
        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        }

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $text = substr($text, $start, $end - $start + 1);

        $data = json_decode($text, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (is_array($data)) {
            return $data;
        }

        $out = '';
        $inString = false;
        $escaped = false;
        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $c = $text[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($c === '\\') {
                    $escaped = true;
                } elseif ($c === '"') {
                    $inString = false;
                } elseif ($c === "\n") {
                    $c = '\\n';
                } elseif ($c === "\r") {
                    $c = '';
                } elseif ($c === "\t") {
                    $c = ' ';
                }
            } elseif ($c === '"') {
                $inString = true;
            }
            $out .= $c;
        }

        $data = json_decode($out, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        return is_array($data) ? $data : null;
    }

    /* =====================================================================
     | Georgia styling for the rich-text fields
     * ===================================================================== */

    protected function applyGeorgiaFontToContentFields(array $data): array
    {
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'] as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->wrapInGeorgiaFont($data[$field]);
            }
        }
        return $data;
    }

    protected function wrapInGeorgiaFont(string $content): string
    {
        if (str_contains($content, 'rich-editor-georgia-wrap')) {
            return $content;
        }

        $style   = 'font-family: Georgia, "Times New Roman", serif; font-size: 12pt; line-height: 1.6;';
        $content = $this->forceInlineStyleOnTags($content, $style);

        return '<div class="rich-editor-georgia-wrap" style="' . $style . '">' . $content . '</div>';
    }

    protected function forceInlineStyleOnTags(string $html, string $style): string
    {
        return preg_replace_callback(
            '/<(p|ul|ol|li|strong|em|b|i|span|div|table|tr|td|th|h[1-6]|a)\b([^>]*)>/i',
            function ($m) use ($style) {
                $tag   = $m[1];
                $attrs = $m[2];

                if (preg_match('/style\s*=\s*"([^"]*)"/i', $attrs, $styleMatch)) {
                    $existing = rtrim(trim($styleMatch[1]), '; ');
                    $merged   = $existing !== '' ? $existing . '; ' . $style : $style;
                    $attrs    = preg_replace('/style\s*=\s*"([^"]*)"/i', 'style="' . $merged . '"', $attrs);
                } else {
                    $attrs .= ' style="' . $style . '"';
                }

                return "<{$tag}{$attrs}>";
            },
            $html
        );
    }

    /* =====================================================================
     | Matching AI values to the real dropdown lists
     * ===================================================================== */

    protected function normaliseLabel(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($s)));
    }

    /**
     * exact match -> alias map -> keyword match -> small typo match. Returns the exact list value or null.
     */
    protected function resolveReferenceName(?string $value, array $list): ?string
    {
        if ($value === null || trim($value) === '' || empty($list)) {
            return null;
        }

        $raw    = trim($value);
        $needle = $this->normaliseLabel($raw);

        foreach ($list as $item) {
            if ($this->normaliseLabel((string) $item) === $needle) {
                return $item;
            }
        }

        $canonical = $this->canonicalLabel($raw);
        if ($canonical !== null) {
            $cn = $this->normaliseLabel($canonical);
            foreach ($list as $item) {
                if ($this->normaliseLabel((string) $item) === $cn) {
                    return $item;
                }
            }
        }

        $stop   = ['level', 'degree', 'years', 'year', 'experience', 'education', 'time', 'job'];
        $tokens = array_filter(
            preg_split('/[^a-z0-9]+/', mb_strtolower($raw), -1, PREG_SPLIT_NO_EMPTY),
            fn($t) => strlen($t) >= 4 && !in_array($t, $stop, true)
        );
        foreach ($tokens as $tok) {
            foreach ($list as $item) {
                if (str_contains($this->normaliseLabel((string) $item), $tok)) {
                    return $item;
                }
            }
        }

        $best = null;
        $bestDist = PHP_INT_MAX;
        $limit = max(2, (int) floor(strlen($needle) * 0.25));
        foreach ($list as $item) {
            $itemN = $this->normaliseLabel((string) $item);
            if (strlen($needle) > 200 || strlen($itemN) > 200) {
                continue;
            }
            $dist = levenshtein($needle, $itemN);
            if ($dist <= $limit && $dist < $bestDist) {
                $best = $item;
                $bestDist = $dist;
            }
        }

        return $best;
    }

    protected function canonicalLabel(string $raw): ?string
    {
        $n = ' ' . preg_replace('/[^a-z0-9]+/i', ' ', strtolower($raw)) . ' ';

        if (preg_match('/\b(phd|doctorate|doctoral)\b/', $n))                                   return 'doctorate';
        if (preg_match('/\b(master|masters|msc|mba|postgraduate)\b/', $n))                      return 'masters';
        if (preg_match('/\b(bachelor|bachelors|bsc|undergraduate)\b/', $n))                     return 'bachelors';
        if (preg_match('/\b(higher national diploma|hnd|diploma)\b/', $n))                      return 'diploma';
        if (preg_match('/\b(certificate|cert)\b/', $n))                                         return 'certificate';
        if (preg_match('/\b(secondary|o level|a level|olevel|alevel|uce|uace)\b/', $n))         return 'secondary';

        if (preg_match('/\b(entry|junior|graduate|intern|internship|fresh)\b/', $n))            return 'entry';
        if (preg_match('/\b(mid|intermediate|middle)\b/', $n))                                  return 'mid';
        if (preg_match('/\b(senior|lead|principal|head|director|manager|executive)\b/', $n))    return 'senior';
        if (preg_match('/\b(expert|specialist)\b/', $n))                                        return 'expert';

        if (preg_match('/\b(full time|fulltime|permanent|regular)\b/', $n))                     return 'full-time';
        if (preg_match('/\b(part time|parttime)\b/', $n))                                       return 'part-time';
        if (preg_match('/\b(contract|contractor|freelance|fixed term)\b/', $n))                 return 'contract';
        if (preg_match('/\b(volunteer|voluntary)\b/', $n))                                      return 'volunteer';
        if (preg_match('/\b(temporary|temp|casual|seasonal)\b/', $n))                           return 'temporary';

        return null;
    }

    protected function listRank(?string $v, array $list): int
    {
        $i = array_search($v, array_values($list), true);

        return $i === false ? -1 : (int) $i;
    }

    protected function htmlToLines(string $html): array
    {
        $t = preg_replace('#</(li|p|h[1-6]|div|tr)>#i', "\n", $html);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $t)), fn($l) => $l !== ''));
    }

    protected function yearsFromLines(array $lines): ?int
    {
        $words = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7,
                  'eight' => 8, 'nine' => 9, 'ten' => 10, 'twelve' => 12, 'fifteen' => 15];

        foreach ($lines as $l) {
            if (!preg_match('/experience/i', $l) || preg_match('/desirable|preferred|advantage|added|plus/i', $l)) {
                continue;
            }
            if (preg_match('/(\d{1,2})\s*(?:\+|plus)?\s*(?:[-\x{2013}]\s*\d{1,2}\s*)?(?:years?|yrs?)/iu', $l, $m)) {
                return (int) $m[1];
            }
            foreach ($words as $w => $n) {
                if (preg_match('/\b' . $w . '\s*(?:\+)?\s*(?:years?|yrs?)\b/i', $l)) {
                    return $n;
                }
            }
        }

        return null;
    }

    protected function educationFromLines(array $lines): ?string
    {
        $found = null;
        $rank  = ['secondary' => 0, 'certificate' => 1, 'diploma' => 2, 'bachelors' => 3, 'masters' => 4, 'doctorate' => 5];

        foreach ($lines as $l) {
            if (preg_match('/desirable|preferred|advantage|added|plus/i', $l)) {
                continue;
            }
            $c = null;
            if      (preg_match('/\b(ph\.?d|doctorate)/i', $l))                                $c = 'doctorate';
            elseif  (preg_match('/\b(master|msc|mba|postgraduate)/i', $l))                     $c = 'masters';
            elseif  (preg_match('/\b(bachelor|bsc|degree|university)/i', $l))                  $c = 'bachelors';
            elseif  (preg_match('/\b(higher national diploma|hnd|diploma)\b/i', $l))           $c = 'diploma';
            elseif  (preg_match('/\bcertificate\b/i', $l))                                     $c = 'certificate';
            elseif  (preg_match('/\b(secondary|o-?level|a-?level|uce|uace)\b/i', $l))          $c = 'secondary';

            if ($c !== null && ($found === null || $rank[$c] > $rank[$found])) {
                $found = $c;
            }
        }

        return $found;
    }

    protected function experienceForYears(int $years, array $levels): ?string
    {
        $levels = array_values($levels);
        if (!$levels) {
            return null;
        }

        $want = $years >= 8 ? 'expert' : ($years >= 5 ? 'senior' : ($years >= 3 ? 'mid' : 'entry'));
        foreach ($levels as $l) {
            if ($this->canonicalLabel((string) $l) === $want) {
                return $l;
            }
        }

        $n   = count($levels);
        $idx = $years <= 2 ? 0 : (int) round(min($years, 10) / 10 * ($n - 1));

        return $levels[min($n - 1, max(0, $idx))];
    }

    /* =====================================================================
     | Smart defaults
     * ===================================================================== */

    protected function normaliseDeadline(mixed $d): ?string
    {
        if (empty($d) || !is_string($d)) {
            return null;
        }
        try {
            $c = Carbon::parse($d);
            return $c->year >= 2000 ? $c->format('Y-m-d') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function applySmartDefaults(array $data, ?string $country = null, string $sourceText = ''): array
    {
        $countrySettings = $country ? ($this->countrySettings[$country] ?? null) : null;
        $ref = $this->getReferenceData($country);

        $verbatim = !empty($data['_verbatim']);
        unset($data['_verbatim']);

        foreach ([
            'industry_name'     => 'industries',
            'category_name'     => 'categories',
            'job_type_name'     => 'job_types',
            'job_location_name' => 'locations',
            'salary_range_name' => 'salary_ranges',
        ] as $field => $refKey) {
            $data[$field] = $this->resolveReferenceName($data[$field] ?? null, $ref[$refKey] ?? []);
        }

        $data['job_title']       = $data['job_title'] ?? '';
        $data['company_name']    = $data['company_name'] ?? '';
        $data['deadline']        = $this->normaliseDeadline($data['deadline'] ?? null) ?: Carbon::now()->addWeeks(2)->format('Y-m-d');
        $data['location_type']   = $data['location_type'] ?? null ?: 'on-site';
        $data['employment_type'] = $data['employment_type'] ?? null ?: 'full-time';

        if (empty($data['job_type_name'])) {
            $data['job_type_name'] = $this->resolveReferenceName($data['employment_type'], $ref['job_types'])
                ?? $this->resolveReferenceName('Full-time', $ref['job_types'])
                ?? $this->resolveReferenceName('Full Time', $ref['job_types']);
        }

        // Experience: respect the AI, but upgrade when the requirements state more years
        $hintLines = $this->htmlToLines((string) ($data['qualifications'] ?? ''));
        if (!$hintLines) {
            $hintLines = $this->htmlToLines($sourceText);
        }

        $exp = $this->resolveReferenceName($data['experience_level_name'] ?? null, $ref['experience_levels']);
        $years = $this->yearsFromLines($hintLines);
        if ($years !== null) {
            $fromYears = $this->experienceForYears($years, $ref['experience_levels']);
            if ($fromYears !== null && ($exp === null || $this->listRank($fromYears, $ref['experience_levels']) > $this->listRank($exp, $ref['experience_levels']))) {
                $exp = $fromYears;
            }
        }
        $data['experience_level_name'] = $exp
            ?? $this->resolveReferenceName('entry level', $ref['experience_levels'])
            ?? ($ref['experience_levels'][0] ?? 'entry level');

        // Education: respect the AI, but upgrade when the requirements name a higher qualification
        $edu = $this->resolveReferenceName($data['education_level_name'] ?? null, $ref['education_levels']);
        $eduWord = $this->educationFromLines($hintLines);
        if ($eduWord !== null) {
            $fromText = $this->resolveReferenceName($eduWord, $ref['education_levels']);
            if ($fromText !== null && ($edu === null || $this->listRank($fromText, $ref['education_levels']) > $this->listRank($edu, $ref['education_levels']))) {
                $edu = $fromText;
            }
        }
        $data['education_level_name'] = $edu
            ?? $this->resolveReferenceName('certificate', $ref['education_levels'])
            ?? ($ref['education_levels'][0] ?? 'Certificate');

        $data['currency']           = ($data['currency'] ?? null) ?: ($countrySettings['currency'] ?? 'AUD');
        $data['country_code']       = $data['country_code'] ?? $country;
        $data['is_resume_required'] = $data['is_resume_required'] ?? true;

        foreach (['email', 'telephone', 'duty_station', 'salary_amount', 'payment_period', 'industry_name', 'category_name', 'work_hours'] as $optional) {
            $data[$optional] = $data[$optional] ?? null;
        }
        foreach (['is_urgent', 'is_featured', 'is_cover_letter_required', 'is_academic_documents_required', 'is_application_required', 'is_whatsapp_contact', 'is_telephone_call'] as $flag) {
            $data[$flag] = $data[$flag] ?? false;
        }

        $title = $data['job_title'];
        $blank = fn(string $f) => empty(trim(strip_tags((string) ($data[$f] ?? ''))));

        if (!$verbatim && $title !== '') {
            if ($blank('job_description')) {
                $data['job_description'] = $this->generateFallbackDescription($title, $data['company_name'] ?: null, $data['duty_station']);
            }
            if ($blank('responsibilities')) {
                $data['responsibilities'] = $this->generateFallbackResponsibilities($title, $data['company_name'] ?: null);
            }
            if ($blank('qualifications')) {
                $data['qualifications'] = $this->generateFallbackQualifications($title);
            }
            if ($blank('skills')) {
                $data['skills'] = $this->generateFallbackSkills($title);
            }
        } elseif ($verbatim && $blank('skills') && $title !== '') {
            $data['skills'] = $this->generateFallbackSkills($title);
        }

        if (empty(trim((string) ($data['meta_description'] ?? ''))) && $title !== '') {
            $data['meta_description'] = $this->generateFallbackMetaDescription($title, $data['company_name'] ?: null, $data['duty_station']);
        }
        if (empty(trim((string) ($data['keywords'] ?? ''))) && $title !== '') {
            $data['keywords'] = $this->generateFallbackKeywords($title);
        }

        $data['application_procedure'] = $data['application_procedure'] ?? '';
        $data['other_positions'] = is_array($data['other_positions'] ?? null) ? $data['other_positions'] : [];

        if (!$verbatim && $title !== '') {
            $data['job_description'] = $this->ensureMinimumDescriptionLength(
                (string) ($data['job_description'] ?? ''), $title, $data['company_name'] ?: null, $data['duty_station']
            );
        }

        return $data;
    }

    protected function ensureMinimumDescriptionLength(string $description, string $title, ?string $company, ?string $location): string
    {
        if (mb_strlen(trim(preg_replace('/\s+/u', ' ', strip_tags($description)))) >= self::GENERATED_DESC_MIN_CHARS) {
            return $description;
        }

        $extra = $this->generateFallbackDescription($title, $company, $location, true);

        return trim(strip_tags($description)) !== '' ? $description . $extra : $extra;
    }

    protected function generateFallbackDescription(string $title, ?string $company, ?string $location, bool $supplement = false): string
    {
        $at   = $company  ? " at {$company}"  : '';
        $in   = $location ? " in {$location}" : '';
        $seed = $title . '|' . $company . '|' . $location;
        $pick = fn(array $pool, string $salt) => $pool[crc32($seed . $salt) % count($pool)];

        $openers = [
            "<p>{$company} has a vacancy for a <strong>{$title}</strong>{$in}.</p>",
            "<p>A <strong>{$title}</strong> is needed{$at}{$in}.</p>",
            "<p>The <strong>{$title}</strong> post{$at} is now open{$in}.</p>",
            "<p>Applications are invited for the position of <strong>{$title}</strong>{$at}{$in}.</p>",
            "<p>There is an opening for a <strong>{$title}</strong>{$at}{$in}, and the team would like it filled soon.</p>",
            "<p>If you have worked as a {$title} before, this vacancy{$at}{$in} may suit you.</p>",
        ];
        if (!$company) {
            $openers[0] = "<p>There is a vacancy for a <strong>{$title}</strong>{$in}.</p>";
        }

        $bodies = [
            'The person we hire will handle the daily work of the role, keep records up to date and report to a supervisor. Some tasks are routine and some arrive without warning, so being organised matters more than any single skill.',
            'Most of the week is spent on the core duties of the post, with regular check-ins with colleagues from other departments. You should be able to plan your own time and finish work without being chased.',
            'This is a hands-on position. You will work closely with the rest of the team, share updates with your supervisor and deal with queries as they come up. Experience in a similar setting will help you settle in quickly.',
            'The work needs someone steady who can follow procedures but also speak up when something is not working. Clear writing and good communication with clients and co-workers are part of the job.',
            'You will join an existing team, so learning how things are already done will be your first task. After that, you will be expected to carry your own workload and suggest improvements where you see them.',
            'The role involves regular contact with colleagues and the public, careful record keeping and meeting deadlines set by management. Training on internal systems will be given where needed.',
        ];

        $closers = [
            '<p>Read the requirements below and apply if they match your background.</p>',
            '<p>Only candidates who meet the requirements listed below should apply.</p>',
            '<p>Please check the closing date and send your application in good time.</p>',
            '<p>Applicants should have the documents mentioned below ready before applying.</p>',
            '<p>Shortlisted candidates will be contacted for interviews.</p>',
            '',
        ];

        $body  = '<p>' . $pick($bodies, 'b') . '</p>';
        $close = $pick($closers, 'c');

        if ($supplement) {
            return $this->capGeneratedDescription($body . $close);
        }

        return $this->capGeneratedDescription($this->scrubAiTells($pick($openers, 'o') . $body . $close));
    }

    protected function generateFallbackResponsibilities(string $title, ?string $company): string
    {
        $seed = $title . '|' . $company;

        $pool = [
            "Handle the day-to-day tasks of the {$title} position",
            'Keep accurate records and update them on time',
            'Work with colleagues in other departments when a task needs more than one team',
            'Report to your supervisor on progress and any problems',
            'Respond to queries from clients, staff or the public',
            'Prepare short weekly and monthly reports',
            "Follow the organisation's policies and procedures",
            'Help train or guide new staff when asked',
            'Look after office or work-area materials and equipment',
            'Attend team meetings and follow up on agreed actions',
            'Suggest simple ways to improve how the work is done',
            'Carry out any other reasonable duties assigned by management',
        ];

        $items = $this->seededPick($pool, $seed, 5 + (crc32($seed . 'n') % 3));

        return '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>';
    }

    protected function generateFallbackQualifications(string $title): string
    {
        $pool = [
            "A certificate, diploma or degree relevant to the {$title} role",
            'Previous work experience in a similar position',
            'Good spoken and written English',
            'Basic computer skills, including Word and Excel',
            'Able to work with little supervision and meet deadlines',
            'Honest, punctual and well organised',
            'Good communication skills',
            'Willingness to work flexible hours when needed',
            'Ability to work well in a team',
        ];

        $items = $this->seededPick($pool, $title . 'q', 4 + (crc32($title . 'qn') % 3));

        return '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>';
    }

    protected function generateFallbackSkills(string $title): string
    {
        $pool = [
            'Communication', 'Teamwork', 'Problem Solving', 'Time Management', 'Report Writing',
            'Attention to Detail', 'Record Keeping', 'Computer Literacy', 'Customer Care',
            'Planning', 'Organisation', 'Adaptability', 'Basic Budgeting', 'Interpersonal Skills',
        ];

        return implode(', ', $this->seededPick($pool, $title . 's', 6 + (crc32($title . 'sn') % 3)));
    }

    protected function generateFallbackMetaDescription(string $title, ?string $company, ?string $location): string
    {
        $at   = $company ? " at {$company}" : '';
        $in   = $location ? " in {$location}" : '';
        $seed = $title . '|' . $company . '|' . $location;

        $variants = [
            "{$title} vacancy{$at}{$in}. See the requirements, duties and how to apply before the deadline.",
            "{$company} is hiring a {$title}{$in}. Check the qualifications needed and apply here.",
            "Job opening: {$title}{$at}{$in}. Read the full details, requirements and application steps.",
            "Apply for the {$title} job{$at}{$in}. Duties, qualifications and closing date inside.",
            "New {$title} vacancy{$in}. Find out what the job involves and who can apply.",
        ];
        if (!$company) {
            $variants[1] = "Hiring now: {$title}{$in}. Check the qualifications needed and apply here.";
        }

        return mb_substr($variants[crc32($seed) % count($variants)], 0, 155);
    }

    protected function generateFallbackKeywords(string $title): string
    {
        $lower = mb_strtolower($title);

        return implode(', ', array_unique([$lower, "{$lower} job", "{$lower} vacancy", "{$lower} jobs", 'jobs', 'vacancies', 'hiring']));
    }

    public function getCountrySettings(string $countryCode): array
    {
        return $this->countrySettings[strtoupper($countryCode)] ?? [
            'currency' => 'AUD',
            'locale'   => 'en_US',
            'timezone' => 'UTC',
        ];
    }

    // =====================================================================
    // >>> PASTE THE CV SECTION HERE (unchanged) <<<
    // From "// CV Extraction" down to the end of the class, with these 4 dead
    // methods DELETED (they are unused and crash because of an undefined $json):
    //   callAiApiForCv, buildCvRequestBody, extractCvErrorMessage, extractCvResponseText
    // That section already contains: getNestedValue, parseJsonResponse, getApiKeyFor,
    // isModelUsable, getOrderedCvModels, cleanLetterHtml, cleanCvHtml. Do not define them again.
    // =====================================================================
}