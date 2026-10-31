<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\{ Http, Log, Cache };
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Job\{Industry, JobCategory, JobLocation, JobType, ExperienceLevel, EducationLevel, SalaryRange};

class AiService
{
    /**
     * Posts shorter than this many words are "thin": the AI writes extra, search-friendly content.
     * At or above it, the employer's text is copied exactly and nothing is added to it.
     * Tune this one number against Search Console (indexing/impressions of short vs long jobs).
     */
    protected const THIN_POST_WORDS = 300;

    protected array $config;
    protected array $fallbackModels;
    protected string $defaultModel;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected array $countrySettings;
    



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


    
    /**
     * Pull the real option lists so the AI selects an EXACT existing name
     * instead of inventing text that will never match a dropdown.
     * Industry is global (not country-scoped); everything else is
     * scoped to the given country where the model supports it.
     */
    protected function getReferenceData(?string $country): array
    {
        $country = $country ? strtoupper($country) : null;

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
    }

    protected function formatReferenceListsForPrompt(array $ref): string
    {
        $line = fn(string $label, array $items) => empty($items)
            ? "{$label}: (none available)"
            : "{$label}: " . implode(' | ', $items);

        return <<<TXT
        AVAILABLE OPTIONS - you MUST choose an exact value from these lists for the corresponding *_name
        fields below (copy the text exactly, including capitalization). If nothing in a list is a reasonable
        match, return null for that field instead of inventing a new value.

        {$line('Industries', $ref['industries'])}
        {$line('Job Categories', $ref['categories'])}
        {$line('Locations (district)', $ref['locations'])}
        {$line('Job Types', $ref['job_types'])}
        {$line('Experience Levels', $ref['experience_levels'])}
        {$line('Education Levels', $ref['education_levels'])}
        {$line('Salary Ranges', $ref['salary_ranges'])}

        For "company_name": this is NOT selected from a list - just write the company's name exactly as
        found in the content (it will be added as a new record afterward).
        TXT;
    }

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
        return $config && !empty($config['api_key']);
    }

    public function getModelConfig(string $model): ?array
    {
        return $this->config[$model] ?? null;
    }

    /**
     * Extract job data from text or URL.
     *
     * For URL sources this now actually fetches the page (previously the raw
     * URL string itself was sent to the model as "content", which no plain
     * chat-completion endpoint can browse - it was just guessing/hallucinating).
     */
    public function extractJobData(string $content, string $sourceType = 'text', ?string $model = null, ?string $country = null): array
    {
        $model = $model ?? $this->defaultModel;
        $sourceUrl = null;

        @set_time_limit(300);

        if ($sourceType === 'url') {
            $sourceUrl = $content;
            $content = $this->fetchUrlContent($content);
        }

        $referenceData = $this->getReferenceData($country);

        // Long employer posting: the body is cut from the original text by PHP, not rewritten by a model.
        $sourceWords = str_word_count(strip_tags($content));
        $body = $sourceWords >= self::THIN_POST_WORDS ? $this->buildVerbatimBody($content) : null;

        $prompt = $this->buildExtractionPrompt($content, $sourceType, $referenceData, $body !== null);

        $result = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
            return $this->callAiApi($m, $key, $prompt, null, true);
        }, expectJson: true);

        if (!empty($result['error'])) {
            throw new \Exception($result['message'] ?? 'No valid job posting could be found in the provided content.');
        }

        if ($body !== null) {
            foreach ($body as $field => $html) {
                if (trim(strip_tags($html)) !== '') {
                    $result[$field] = $html;
                }
            }
        } else {
            $result = $this->enrichThinPosting($result, $model, $sourceWords);
        }
        $result = $this->applySmartDefaults($result, $country);
        $result = $this->applyApplicationLinkHandling($result, $sourceUrl);
        return $this->applyGeorgiaFontToContentFields($result);
    }

    /**
     * COPY-AND-PASTE MODE for long employer postings.
     *
     * Employers notice when a model rewords their advert, and no prompt can promise a model
     * will copy 1,500 words without dropping a line. So for a long posting the body is cut out
     * of the original text by PHP (by its own headings) and never passes through the model at
     * all. The model is then only asked for the metadata (title, company, dates, categories).
     *
     * Returns null when the text has no recognisable duties/qualifications headings, in which
     * case the normal model-based extraction (with its word-for-word rule) is used instead.
     */
    protected function buildVerbatimBody(string $text): ?array
    {
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = [];
        foreach (explode("\n", $text) as $l) {
            $l = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $l));
            if ($l !== '') {
                $lines[] = $l;
            }
        }

        // Where the advert itself starts (everything above is site header/menu text).
        $start = 0;
        foreach ($lines as $i => $l) {
            if (preg_match('/^job title\s*:/i', $l)) { $start = $i; break; }
        }
        if ($start === 0) {
            foreach ($lines as $i => $l) {
                if (preg_match('/^(about\b.{0,60}|job purpose:?|job summary:?|overview:?|background:?)$/i', $l)) { $start = $i; break; }
            }
        }

        $respRe    = '/^(key |main )?(duties|responsibilities|duties and responsibilities|roles? and responsibilities)\s*:?$/i';
        $kpiRe     = '/^key performance indicators\s*:?$/i';
        $qualRe    = '/^(required |minimum |academic )?(qualifications|requirements|qualifications and experience|education and experience|person specification)( and (experience|requirements))?\s*:?$/i';
        $skillRe   = '/^(required |key |core )?(skills|competencies|skills and competencies|skills and abilities)\s*:?$/i';
        $applyRe   = '/^(how to apply|application procedure|application process)\s*:?$/i';
        $closingRe = '/^(disclaimer\b|we encourage\b|.*\bequal opportunit|.*\bdoes not charge\b)/i';
        $noiseRe   = '/^(apply to position|use my indeed resume|apply using linkedin|apply now|job openings.*|company website.*|job description)$/i';
        $endRe     = '/^(powered by|.*sharing button)/i';

        $b = ['desc' => [], 'resp' => [], 'qual' => [], 'skill' => [], 'apply' => [], 'closing' => []];
        $state = 'desc';

        foreach (array_slice($lines, $start) as $l) {
            if (preg_match($endRe, $l)) {
                break;
            }
            if (preg_match($noiseRe, $l)) {
                continue;
            }
            if ($state !== 'closing' && preg_match($closingRe, $l)) {
                $state = 'closing';
            } elseif (mb_strlen($l) <= 80) {
                if (preg_match($kpiRe, $l)) {
                    $state = 'resp';           // keep the heading line itself
                } elseif (preg_match($respRe, $l)) {
                    $state = 'resp'; continue;
                } elseif (preg_match($qualRe, $l)) {
                    $state = 'qual'; continue;
                } elseif (preg_match($skillRe, $l)) {
                    $state = 'skill'; continue;
                } elseif (preg_match($applyRe, $l)) {
                    $state = 'apply'; continue;
                }
            }
            $b[$state][] = $l;
        }

        if (empty($b['resp']) && empty($b['qual'])) {
            return null;
        }

        $closing = $this->renderVerbatimBlocks($b['closing'], false);

        return [
            'job_description'       => $this->renderVerbatimBlocks($b['desc'], false) . $closing,
            'responsibilities'      => $this->renderVerbatimBlocks($b['resp'], true),
            'qualifications'        => $this->renderVerbatimBlocks($b['qual'], true),
            'skills'                => $this->renderVerbatimSkills($b['skill']),
            'application_procedure' => $this->renderVerbatimBlocks($b['apply'], false),
        ];
    }

    private function isVerbatimHeading(string $l): bool
    {
        $words = preg_split('/\s+/', $l);
        $n = count($words);

        if (preg_match('/^\d{1,2}[.)]\s+\S/', $l)) {
            return $n <= 10 && !preg_match('/[.;,]$/', $l);
        }
        if (preg_match('/:$/', $l)) {
            return $n <= 6;
        }
        // "Job Title: Deputy Manager, Planning" is a data line, not a heading
        if (preg_match('/[.;,!?]$/', $l) || preg_match('/[:,]/', $l) || $n > 5) {
            return false;
        }
        $small = ['and', 'of', 'the', 'for', 'to', 'in', 'on', '&', 'a'];
        foreach ($words as $w) {
            if (!in_array(mb_strtolower($w), $small, true) && !preg_match('/^[\p{Lu}\d]/u', $w)) {
                return false;
            }
        }
        return true;
    }

    /** Lines -> HTML. Text is escaped but never changed. Headings stay headings. */
    private function renderVerbatimBlocks(array $lines, bool $asList): string
    {
        $esc  = fn(string $s) => htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '';
        $open = false;
        $close = function () use (&$html, &$open) {
            if ($open) { $html .= '</ul>'; $open = false; }
        };

        foreach ($lines as $l) {
            if ($this->isVerbatimHeading($l)) {
                $close();
                $html .= '<p><strong>' . $esc($l) . '</strong></p>';
                continue;
            }
            if (preg_match('/:$/', $l)) { // a lead-in sentence such as "Performance will be assessed against:"
                $close();
                $html .= '<p>' . $esc($l) . '</p>';
                continue;
            }
            $item = preg_replace('/^[\x{2022}\x{25AA}\x{25CF}\x{00B7}*\-]\s*/u', '', $l);
            if ($asList) {
                if (!$open) { $html .= '<ul>'; $open = true; }
                $html .= '<li>' . $esc($item) . '</li>';
            } else {
                $html .= '<p>' . $esc($item) . '</p>';
            }
        }
        $close();

        return $html;
    }

    /** Short skill names -> "A, B, C". Long sentences under headings stay as headed lists. */
    private function renderVerbatimSkills(array $lines): string
    {
        if (empty($lines)) {
            return '';
        }

        $short = true;
        foreach ($lines as $l) {
            if (count(preg_split('/\s+/', $l)) > 4 || $this->isVerbatimHeading($l)) {
                $short = false;
                break;
            }
        }
        if ($short) {
            $items = array_map(fn($l) => rtrim(preg_replace('/^[\x{2022}\x{25AA}\x{25CF}\x{00B7}*\-]\s*/u', '', $l), '.;, '), $lines);
            return htmlspecialchars(implode(', ', $items), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->renderVerbatimBlocks($lines, true);
    }

    /**
     * A one-line post ("We need an enrolled nurse or midwife in Kanaaba, 0701...")
     * would become a skeleton page that search engines ignore. When the description
     * we ended up with is under ~120 words, ask the model to write a real, role-specific
     * description from the facts that ARE known. Long, real postings never reach this.
     * If the model fails, the static fallbacks in applySmartDefaults() still run.
     */
    protected function enrichThinPosting(array $data, string $model, ?int $sourceWords = null): array
    {
        $title = trim((string) ($data['job_title'] ?? ''));
        if ($title === '') {
            return $data;
        }

        $current = trim(strip_tags((string) ($data['job_description'] ?? '')));

        // Thin = the whole source post under THIN_POST_WORDS (300) words (text/URL), or, for an image where the
        // source size is unknown, a description under 120 words.
        $isThin = $sourceWords !== null ? $sourceWords < self::THIN_POST_WORDS : str_word_count($current) < 120;
        if (!$isThin) {
            return $data;
        }

        // A post with a real description of its own (roughly 60+ words) keeps that text untouched;
        // our content is added after it. Only a bare one-liner is replaced.
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
            $current  !== '' ? "What the original post said" . ($append ? ' (it will be shown above your text, so do not repeat it)' : '') . ": {$current}" : null,
        ]);
        $factList = implode("\n", $facts);

        $prompt = <<<PROMPT
        You write job adverts for a Ugandan and East African job board. The original post is very short, so write
        the job description a real hiring officer would have written for it.

        KNOWN FACTS:
        {$factList}

        {$humanRules}

        REQUIREMENTS:
        - 180 to 250 words, in 3 to 5 short <p> paragraphs of uneven length.
        - SEARCH (SEO): use the job title, the place name and the profession's real search terms where they fit
          naturally (for example the role, the place and the sector, and what people call this job), each once or
          twice. Never repeat a phrase just to rank.
        - ANSWER ENGINES (AEO): the first paragraph must answer, in plain sentences, who is hiring (or that the
          employer is not named), what the job is, and where. Another paragraph should state clearly who can apply
          (type of qualification and experience a post like this normally asks for, worded as "suits", not as a
          binding requirement). Write sentences an assistant could quote on their own as an answer.
        - Describe what this person actually does day to day in a setting like the one named, who they work with,
          and what kind of candidate suits the post. Be specific to this profession.
        - Mention the job title and the location naturally once or twice, never forced.
        - Do NOT invent the employer name, salary, benefits, deadline, number of vacancies or qualifications as
          hard requirements. Do not include phone numbers, emails or links.
        - Return ONLY the HTML paragraphs. Plain <p> tags with no attributes. No markdown, no headings, no code fences.
        PROMPT;

        try {
            $text = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
                return $this->callAiApi($m, $key, $prompt);
            }, expectJson: false);

            $text = $this->cleanLetterHtml(is_string($text) ? $text : $this->extractTextFromResult($text));
            $text = $this->scrubAiTells($text);

            if (str_word_count(strip_tags($text)) >= 110) {
                $data['job_description'] = $append
                    ? trim((string) $data['job_description']) . $text
                    : $text;
            }
        } catch (\Throwable $e) {
            Log::warning('Thin posting enrichment failed, using static fallback', ['error' => $e->getMessage()]);
        }

        return $data;
    }

    /**
     * Fetch and clean a URL's page content so the AI actually has real
     * page text to extract from, instead of just the URL string.
     */
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
        $text = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $text = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $text);
        // keep paragraph/list boundaries as line breaks so headings and bullets survive
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

        $referenceData = $this->getReferenceData($country);
        $prompt = $this->buildImageExtractionPrompt($referenceData);

        $result = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt, $imageBase64) {
            return $this->callAiApi($m, $key, $prompt, $imageBase64, true);
        }, expectJson: true);

        if (!empty($result['error'])) {
            throw new \Exception($result['message'] ?? 'No job details could be read from the image.');
        }

        $result = $this->enrichThinPosting($result, $model);
        $result = $this->applySmartDefaults($result, $country);
        $result = $this->applyApplicationLinkHandling($result);
        return $this->applyGeorgiaFontToContentFields($result);
    }

    /**
     * Turns a raw application_link into a "To apply, click here" hyperlink inside
     * application_procedure, and makes sure no raw link/email/phone leaked into
     * the wrong fields (belt-and-braces on top of the prompt instructions).
     *
     * Also normalizes "telephone" so that multiple contact numbers are always
     * comma-separated, regardless of how the source/AI formatted them.
     */
    protected function applyApplicationLinkHandling(array $data, ?string $sourceUrl = null): array
    {
        $link = $data['application_link'] ?? null;

        // Pulled from a web page but the posting showed no separate apply URL
        // (Breezy, Workable etc. hide it behind a button): apply on the source page.
        if (empty($link) && $sourceUrl && filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            $link = $sourceUrl;
        }

        if (!empty($link) && filter_var($link, FILTER_VALIDATE_URL)) {
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $linkLine = '<p>To apply for this job, <a href="' . $safeLink . '" target="_blank" rel="noopener noreferrer">click here</a>.</p>';

            // Keep any procedure the employer actually wrote; the link goes after it.
            $existing = (string) ($data['application_procedure'] ?? '');
            $data['application_procedure'] = trim(strip_tags($existing)) !== '' ? $existing . $linkLine : $linkLine;
            $data['is_application_required'] = true;
        }

        // Belt-and-braces: contact details and raw URLs belong in their own fields,
        // never in the body text.
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills'] as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $clean = preg_replace('#https?://\S+#i', '', $data[$field]);
                $clean = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '', $clean);
                // Ugandan-style phone numbers: 0701 220529, +256 701 220 529, 0701-220-529
                $clean = preg_replace('/(?<![\d,.])(?:\+?256[\s-]?|0)7\d{2}[\s-]?\d{3}[\s-]?\d{3}(?![\d,])/', '', $clean);
                $data[$field] = $clean;
            }
        }

        // Belt-and-braces: make sure multiple contact numbers are consistently
        // comma-separated, even if the source/AI used "/", ";", "and", "or", etc.
        if (!empty($data['telephone']) && is_string($data['telephone'])) {
            $data['telephone'] = $this->normalizeTelephone($data['telephone']);
        }

        // Every job gets a "never pay for this job" warning in the application procedure,
        // worded and placed differently from job to job.
        $data = $this->applyPaymentWarning($data);

        unset($data['application_link']); // not a real form field - already folded into application_procedure
        return $data;
    }

    /**
     * Pools of "do not pay" warnings. All CAPITALS, all different in voice, so a
     * page full of jobs never repeats one stock sentence. The meaning is always:
     * do not pay money for this advert or for any part of the recruitment process.
     */
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

    /**
     * Adds one warning to application_procedure (and writes a short, plain
     * procedure if there wasn't one). The warning is picked from the job's own
     * details, so the same job always gets the same wording on re-runs but
     * neighbouring jobs differ. Position (before/after) and styling vary too.
     */
    protected function applyPaymentWarning(array $data): array
    {
        $procedure = (string) ($data['application_procedure'] ?? '');
        $plain     = trim(strip_tags($procedure));

        // Source already carries a capitalised "do not pay" notice? Leave it alone.
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
        $before   = crc32($seedBase . 'p') % 4 === 0; // mostly after, sometimes first

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

    /**
     * Wording a reader (or a detector) would recognise as machine-written.
     * Used in every prompt that asks the model to WRITE job content.
     */
    protected function humanWritingRules(): string
    {
        return <<<RULES
        HUMAN WRITING RULES (anything you write yourself, not text copied from the source):
        - Write the way a hiring officer in East Africa would type a real advert: plain, direct, specific.
        - Never use these words or phrases: delve, leverage, utilize, synergy, dynamic, passionate, vibrant,
        fast-paced, thrive, seamless, robust, cutting-edge, state-of-the-art, game-changer, pivotal, tapestry,
        landscape, realm, "in today's", "join our team", "exciting opportunity", "rewarding career",
        "take your career to the next level", "a proven track record", "results-driven", "self-starter",
        "plays a crucial role", "ensure that", "it is important to note".
        - Do not use em dashes or en dashes. Use a comma, a full stop or a plain hyphen.
        - Do not use emojis, rhetorical questions, or a closing sentence that sums up the whole advert.
        - Avoid "not only ... but also", and avoid listing things in threes every time.
        - Vary sentence length on purpose: some short, some long. Do not start two sentences the same way.
        - Prefer concrete things (what the person actually does, uses, reports to) over adjectives.
        - Lists do not need equal length: some bullets are a few words, others a full line.
        - Do not repeat the job title in every sentence, and do not repeat keywords to please search engines.
        RULES;
    }

    /**
     * Last line of defence for text WE generate (not verbatim source text):
     * remove the small habits that make copy look machine-written.
     */
    protected function scrubAiTells(string $text): string
    {
        $text = preg_replace('/\s*[\x{2014}\x{2013}]\s*/u', ', ', $text);
        $text = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"], ["'", "'", '"', '"'], $text);
        // emojis / pictographs
        $text = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);

        $swap = [
            '/\bleverag(e|es|ed|ing)\b/i'      => 'use',
            '/\butili[sz](e|es|ed|ing)\b/i'    => 'use',
            '/\bdelv(e|es|ed|ing) into\b/i'    => 'look into',
            '/\bseamless(ly)?\b/i'             => 'smooth',
            '/\bcutting-edge\b/i'              => 'modern',
            '/\bfast-paced\b/i'                => 'busy',
            '/\bdynamic\b/i'                   => 'active',
            '/\bpassionate\b/i'                => 'committed',
            '/\bvibrant\b/i'                   => 'lively',
            '/\bpivotal\b/i'                   => 'important',
            '/\bresults-driven\b/i'            => 'focused',
        ];
        $text = preg_replace(array_keys($swap), array_values($swap), $text);

        return preg_replace('/,\s*,/', ',', $text);
    }

    /** Scrub every string value that we asked the model to write from scratch. */
    protected function scrubGeneratedFields(array $data): array
    {
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills', 'meta_description', 'keywords'] as $f) {
            if (!empty($data[$f]) && is_string($data[$f])) {
                $data[$f] = $this->scrubAiTells($data[$f]);
            }
        }
        return $data;
    }

    /** Stable pick: same job => same result, different jobs => different results. */
    protected function seededPick(array $pool, string $seed, int $count = 1): array
    {
        $scored = [];
        foreach ($pool as $i => $item) {
            $scored[] = [crc32($seed . '#' . $i), $item];
        }
        usort($scored, fn($a, $b) => $a[0] <=> $b[0]);

        return array_map(fn($s) => $s[1], array_slice($scored, 0, $count));
    }

    /**
     * Normalize a telephone field so that when more than one contact number
     * is present (WhatsApp, callable, or both), they end up comma-separated
     * in a single consistent format instead of "/", ";", "and", "or", etc.
     */
    protected function normalizeTelephone(string $telephone): string
    {
        $normalized = preg_replace('/\s*(\/|;|\band\b|\bor\b|\|)\s*/i', ', ', $telephone);
        $numbers = array_filter(array_map('trim', explode(',', $normalized)), fn($n) => $n !== '');

        return implode(', ', array_values(array_unique($numbers)));
    }

    /**
     * Enhance a field using AI. This is plain HTML text, NOT JSON - it must
     * NOT be routed through the JSON parser (that was the reason every
     * "AI Enhance" call previously returned the literal string "[]").
     */
    public function enhanceField(string $fieldName, string $content, string $instruction, ?string $model = null): string
    {
        $model = $model ?? $this->defaultModel;
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

        $result = $this->scrubGeneratedFields($result); // all of this was written by the model
        $result['job_title'] = $result['job_title'] ?? $title;
        $result['company_name'] = $result['company_name'] ?? ($company ?? '');
        $result = $this->applySmartDefaults($result, $country);
        $result = $this->applyApplicationLinkHandling($result);
        return $this->applyGeorgiaFontToContentFields($result);
    }

    /**
     * Call AI API with fallback support.
     *
     * When $expectJson is true, a malformed/empty response from one model is
     * treated as that model's failure and the NEXT model in the fallback
     * chain is tried automatically - previously a parse failure just quietly
     * returned an empty array disguised as a success.
     */
    protected function callWithFallback(string $primaryModel, string $prompt, callable $callFn, bool $expectJson = true): array|string
    {
        $errors = [];

        // Several models x up to 90s each can outlive PHP's default limit, which kills the
        // request and sends the browser an HTML error page ("JSON.parse: unexpected character").
        @set_time_limit(300);

        // Build the chain: the requested primary model first, then the standard
        // fallback order (gemini → claude → openai → cohere). Cohere is ALWAYS
        // appended as the final attempt, regardless of what the config says.
        $chain = array_merge(
            [$primaryModel],
            ['gemini', 'claude', 'openai'],
            ['grok', 'mistral'],   // only tried if enabled — filtered below
            ['cohere'],            // guaranteed last resort
        );

        $models = array_values(array_unique($chain));

        foreach ($models as $model) {
            if (!$this->isModelEnabled($model)) {
                $errors[$model] = 'not enabled (missing API key)';
                continue;
            }

            try {
                $config = $this->getModelConfig($model);
                $raw = $callFn($model, $config['api_key']);

                if (!$expectJson) {
                    return is_array($raw) ? $this->extractTextFromResult($raw) : (string) $raw;
                }

                return $this->parseJsonOrFail($raw, $model);
            } catch (\Throwable $e) { // Throwable: a TypeError must also fall through to the next model
                // Store the error message with the model name as key
                $errorMessage = $e->getMessage();
                
                // Clean up the error message - remove "API error (model): " prefix if present
                $cleanError = preg_replace('/^API error \([^)]+\):\s*/', '', $errorMessage);
                $errors[$model] = $cleanError ?: $errorMessage;
                
                Log::warning("AI call failed for model '{$model}'", ['error' => $e->getMessage()]);
            }
        }

        // Return errors as a structured array
        throw new \Exception(json_encode([
            'type' => 'model_errors',
            'errors' => $errors
        ]));
    }

    /**
     * Strictly parse a model's output into a non-empty array, or throw.
     * Throwing here (rather than returning []) is what lets callWithFallback
     * fall through to the next model instead of silently "succeeding" with
     * nothing to show on the form.
     */
    protected function parseJsonOrFail(mixed $result, string $model): array
    {
        if (is_array($result) && !empty($result)) {
            return $result;
        }

        if (is_string($result)) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $result);
            $clean = preg_replace('/\s*```\s*$/i', '', $clean);
            $clean = trim($clean);

            $decoded = $this->decodeJsonLenient($clean);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }

            // Throw with the raw response
            throw new \Exception("Response could not be parsed as JSON. Raw response: " . substr($result, 0, 500));
        }

        throw new \Exception('Empty or invalid response.');
    }

    /**
     * Decode a model's JSON reply, tolerating the usual slips on long outputs:
     * chatter before/after the object, and raw line breaks or tabs inside string
     * values (which JSON forbids). A reply that is cut off is NOT repaired, so a
     * truncated posting can never be saved as if it were complete.
     */
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

        // Escape control characters that sit inside string values.
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

    protected function callAiApi(string $model, string $apiKey, string $prompt, ?string $imageBase64 = null, bool $json = false): array|string
    {
        $config = $this->getModelConfig($model);
        if (!$config) {
            throw new \Exception("Model '{$model}' not configured.");
        }

        $endpoint = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $config['max_tokens'] ?? 4096;
        // A long posting copied word-for-word as JSON easily passes 4096 tokens; the reply was
        // then cut off mid-string and could not be parsed. Cohere rejects values above 4096.
        if ($model !== 'cohere') {
            $maxTokens = max($maxTokens, 8192);
        }

        // ✅ FIX: Set headers based on model
        $headers = ['Content-Type' => 'application/json'];
        
        if ($model === 'openai' || $model === 'grok' || $model === 'mistral' || $model === 'cohere') {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key'] = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }
        // Gemini uses URL param, not header

        $body = $this->buildRequestBody($model, $apiKey, $modelName, $maxTokens, $prompt, $imageBase64, $json);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->withHeaders($headers)
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw $this->buildApiException($model, $response);
        }

        return $this->extractResponseText($model, $response->json());
    }

    /**
     * Build the request body for each AI model
     */
    private function buildRequestBody(string $model, string $apiKey, string $modelName, int $maxTokens, string $prompt, ?string $imageBase64, bool $json = false): array
    {
        switch ($model) {
            case 'openai':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $imageBase64
                                ? [
                                    ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                                    ['type' => 'text', 'text' => $prompt],
                                ]
                                : $prompt
                        ]
                    ],
                ];

            case 'claude':
                $content = $imageBase64
                    ? [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['type' => 'text', 'text' => $prompt],
                    ]
                    : $prompt;

                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $content]],
                ];

            case 'gemini':
                $parts = $imageBase64
                    ? [
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['text' => $prompt],
                    ]
                    : [['text' => $prompt]];

                return [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => array_filter([
                        'maxOutputTokens'  => $maxTokens,
                        // JSON mode: the model can't wrap the reply in prose or leave a bare newline in a string
                        'responseMimeType' => $json ? 'application/json' : null,
                    ]),
                ];

            case 'grok':
            case 'cohere':
            case 'mistral':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ];

            default:
                throw new \Exception("Unsupported model: {$model}");
        }
    }

    /**
     * Build a clean API exception with the error message from the response
     */
    private function buildApiException(string $model, $response): \Exception
    {
        $errorBody = $response->body();
        $errorData = json_decode($errorBody, true);

        // Extract the actual error message from various response formats
        $errorMessage = $this->extractErrorMessage($errorData, $errorBody);

        return new \Exception("API error ({$model}): " . $errorMessage);
    }

    /**
     * Extract error message from API response data
     */
    private function extractErrorMessage(?array $errorData, string $fallback): string
    {
        // A gateway/HTML error page is not JSON, so json_decode() gave null. The old
        // `array` type hint turned that into an uncaught TypeError and an HTML 500 page.
        if (!is_array($errorData)) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($fallback)));
            return $plain !== '' ? mb_substr($plain, 0, 300) : 'Empty or non-JSON error response from the provider.';
        }

        // Common error message paths in different API responses
        $paths = [
            'error.message',
            'error.error.message',
            'error.error',
            'message',
            'error'
        ];

        foreach ($paths as $path) {
            $value = $this->getNestedValue($errorData, $path);
            if ($value && is_string($value)) {
                return $value;
            }
        }

        // If no message found in the JSON, try to clean the raw body
        $cleaned = preg_replace('/\{"error":\{"message":"([^"]+)"[^}]*\}/', '$1', $fallback);
        if ($cleaned !== $fallback) {
            return $cleaned;
        }

        // Fallback: clean up the raw response
        return preg_replace('/\s+/', ' ', trim($fallback));
    }

    /**
     * Get nested array value using dot notation
     */
    // private function getNestedValue(array $data, string $path)
    // {
    //     $keys = explode('.', $path);
    //     $current = $data;

    //     foreach ($keys as $key) {
    //         if (!isset($current[$key])) {
    //             return null;
    //         }
    //         $current = $current[$key];
    //     }

    //     return $current;
    // }

    /**
     * Extract the text content from the API response
     */
    private function extractResponseText(string $model, array $data): string
    {
        $text = match ($model) {
            'openai', 'grok', 'mistral' => $data['choices'][0]['message']['content'] ?? '',
            'claude' => $data['content'][0]['text'] ?? '',
            'gemini' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'cohere' => $data['message']['content'][0]['text'] ?? 
                        $data['text'] ?? 
                        $data['response'] ?? 
                        $data['generation'] ?? 
                        '',
            default => json_encode($data),
        };

        // Log empty responses for debugging
        if (empty($text)) {
            Log::warning("Empty response from AI model '{$model}'", ['full_response' => json_encode($data)]);
        }

        return $text;
    }

    protected function extractTextFromResult(mixed $result): string
    {
        if (is_string($result)) return $result;

        if (is_array($result)) {
            foreach (['text', 'content', 'enhanced', 'job_description'] as $key) {
                if (isset($result[$key]) && is_string($result[$key])) {
                    return $result[$key];
                }
            }
            $first = reset($result);
            if (is_string($first)) return $first;
            return '';
        }

        return (string) $result;
    }

    /**
     * Wrap the rich-text content fields in Georgia, 12px font, applied here
     * in PHP (so it's guaranteed to produce valid, correctly-escaped HTML)
     * rather than asking the AI to embed style="..." attributes itself -
     * that was producing unescaped quotes inside the JSON string values,
     * which is exactly what was breaking json_decode() and causing
     * extraction results to silently come back empty.
     */
    protected function applyGeorgiaFontToContentFields(array $data): array
    {
        // All 5 rich-text fields shown to the user: Job Description, Key Responsibilities,
        // Qualifications, Required Skills, Application Procedure.
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'] as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->wrapInGeorgiaFont($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Wraps content in Georgia 12pt, AND pushes that same inline style onto every
     * inner tag (p, li, etc.), not just the outer wrapper div. Just styling the
     * wrapper isn't enough if the rich-text editor's own stylesheet sets a
     * font-size on child tags directly - a same-specificity rule on the child
     * itself beats an inherited value from an ancestor, which is why some
     * fields (e.g. Required Skills, which previously wasn't wrapped at all)
     * rendered at a different size than the rest.
     */
    protected function wrapInGeorgiaFont(string $content): string
    {
        if (str_contains($content, 'rich-editor-georgia-wrap')) {
            return $content; // already wrapped
        }

        $style = 'font-family: Georgia, "Times New Roman", serif; font-size: 12pt; line-height: 1.6;';
        $content = $this->forceInlineStyleOnTags($content, $style);

        return '<div class="rich-editor-georgia-wrap" style="' . $style . '">' . $content . '</div>';
    }

    /**
     * Merges the given inline style onto every block/text-level tag found in
     * the HTML fragment (adding a style="" attribute, or merging into an
     * existing one), so the font-size can't be silently overridden by
     * more specific CSS elsewhere on the page.
     */
    protected function forceInlineStyleOnTags(string $html, string $style): string
    {
        return preg_replace_callback(
            '/<(p|ul|ol|li|strong|em|b|i|span|div|table|tr|td|th|h[1-6]|a)\b([^>]*)>/i',
            function ($m) use ($style) {
                $tag = $m[1];
                $attrs = $m[2];

                if (preg_match('/style\s*=\s*"([^"]*)"/i', $attrs, $styleMatch)) {
                    $existing = rtrim(trim($styleMatch[1]), '; ');
                    $merged = $existing !== '' ? $existing . '; ' . $style : $style;
                    $attrs = preg_replace('/style\s*=\s*"([^"]*)"/i', 'style="' . $merged . '"', $attrs);
                } else {
                    $attrs .= ' style="' . $style . '"';
                }

                return "<{$tag}{$attrs}>";
            },
            $html
        );
    }

    protected function buildExtractionPrompt(string $content, string $sourceType, array $referenceData = [], bool $bodyCaptured = false): string
    {
        $twoWeeksAhead = Carbon::now()->addWeeks(2)->format('Y-m-d');
        $referenceBlock = $this->formatReferenceListsForPrompt($referenceData);
        $humanRules = $this->humanWritingRules();

        $bodyNote = $bodyCaptured
            ? "BODY TEXT ALREADY CAPTURED - METADATA ONLY:\n                The description, responsibilities, qualifications, skills and application text of this posting have\n                already been copied exactly by the system. Return null for job_description, responsibilities,\n                qualifications, skills and application_procedure (do NOT output them), and fill in every other field\n                by reading the content below."
            : '';

        $sourceInstruction = $sourceType === 'url'
            ? 'The CONTENT below was fetched directly from the job posting page. Extract the job details from it.'
            : 'The CONTENT below is pasted job description text.';

        return <<<PROMPT
                You are an expert job-board data extraction agent.

                SOURCE TYPE: {$sourceType}
                {$sourceInstruction}

                {$referenceBlock}

                {$bodyNote}

                ABSOLUTE RULE - PRESERVE VERBATIM:
                If job_description, responsibilities, qualifications, skills, or application_procedure are present in
                the content, copy them into the JSON EXACTLY as given - same wording, same order, same structure,
                including any tables (as HTML <table> markup). Do NOT summarize, shorten, paraphrase, or "clean up"
                anything that is actually present. Only WRITE new content for a field when that field is completely
                absent from the source.

                FULL POSTINGS FROM EMPLOYER SITES (copy exactly, add nothing):
                - Ignore page furniture that is not part of the advert: buttons and menu text such as "Apply To
                Position", "Use My Indeed Resume", "Apply Using LinkedIn", "Job Openings", share links, cookie notices.
                - The job details block at the top (Department, Reporting To, Years of Experience, Location, Travel
                Required, etc.) stays at the start of job_description as plain <p> lines, in the original order.
                - "About the organisation" and "Job Purpose" text goes into job_description, word for word.
                - Responsibilities: keep every numbered or named sub-heading as <p><strong>heading</strong></p>
                followed by its own <ul>. Keep the original order and wording. If the advert has a "Key Performance
                Indicators" section, add it at the end of responsibilities the same way.
                - Qualifications: the "Required Qualifications and Experience" items, one <li> each, word for word.
                - Skills: if the advert gives short skill names, return them comma-separated. If it gives long
                sentences grouped under headings (Technical, Leadership, Interpersonal...), keep the headings as
                <p><strong>..</strong></p> and the items as <ul><li>, word for word.
                - Closing statements (equal opportunity, "we do not charge any fee" disclaimers) stay at the end of
                job_description, word for word.
                - duty_station comes from the "Location:" line of the advert if it has one.
                - Do not add, rephrase, merge or drop any sentence. Do not add your own tips or notices; the
                "do not pay" notice is added separately.

                VERY SHORT POSTS (under about 300 words, e.g. a one-line WhatsApp-style vacancy):
                - Put exactly what the post says into job_title, duty_station, telephone and email. Use a clean,
                properly capitalised title (e.g. "Enrolled Nurse / Midwife") and never put "we need" in the title.
                - Do not invent an employer, salary or deadline. A short job_description is fine here, because a
                fuller one is written in a separate step. Never put the phone number or email in the description.

                CRITICAL - RESPONSIBILITIES & QUALIFICATIONS MUST BE HTML LISTS:
                When you generate or return responsibilities and qualifications, they MUST be formatted as HTML
                unordered lists using <ul> and <li> tags. NEVER return them as plain text with dashes, asterisks,
                numbers, commas, or periods as separators. ALWAYS use proper HTML list format.

                CORRECT EXAMPLE:
                "responsibilities": "<ul><li>Lead and manage key initiatives</li><li>Collaborate with cross-functional teams</li><li>Ensure timely delivery of projects</li></ul>"
                
                INCORRECT EXAMPLE (DO NOT DO THIS):
                "responsibilities": "Lead and manage key initiatives, Collaborate with cross-functional teams, Ensure timely delivery of projects"
                
                INCORRECT EXAMPLE (DO NOT DO THIS):
                "responsibilities": "- Lead and manage key initiatives\n- Collaborate with cross-functional teams\n- Ensure timely delivery of projects"

                CONTACT INFO MUST NOT APPEAR IN BODY CONTENT:
                Emails, phone numbers, WhatsApp numbers, and application links/URLs must NEVER appear inside
                job_description, responsibilities, qualifications, or skills - even if they appear that way in the
                source. Strip them out of those fields entirely and instead put them ONLY in the dedicated fields
                below (email, telephone, application_link).

                APPLICATION LINK HANDLING:
                - If an application URL/link is present anywhere in the content, put the raw URL in "application_link"
                and leave application_procedure to describe the general process in plain text (no raw link inside it -
                the raw link will be turned into a "click here" hyperlink automatically afterward).
                - If no link is present but there is a described procedure (email a CV, visit an office, etc.), put that
                in application_procedure as normal and leave application_link null.

                PHONE / CONTACT HANDLING:
                - If a phone number is present, put it in "telephone".
                - If more than one contact number is present (WhatsApp, callable, or both), list ALL of them in
                "telephone" separated by commas, e.g. "+256700000000, +256770000000" - never join them with "/",
                "and", "or", or on separate lines.
                - Set is_whatsapp_contact = true if any of the numbers is explicitly described as WhatsApp.
                - Set is_telephone_call = true if any of the numbers is described as callable, or if no qualifier is
                given at all for a number (default to true for that number in that case).
                - Both flags can be true at once if the content indicates numbers work for both purposes.

                APPLICATION PROCEDURE NOTE:
                Do NOT write any warning about paying money or fees in application_procedure. A "do not pay"
                notice is added automatically afterwards.

                {$humanRules}

                CONTENT QUALITY & SEO RULE (applies to anything you WRITE rather than copy verbatim):
                - Write natural, professional, human-sounding copy that reads like it was typed by an
                experienced HR officer, not a template.
                - Naturally weave in the job title and relevant industry, skill, and location keywords so the
                content is well-optimized for search engines, without keyword-stuffing or awkward repetition.
                - If the final job_description (verbatim, generated, or a mix) would end up under roughly 120 words, 
                    treat that as too thin for SEO: expand it with additional relevant, natural, role-appropriate 
                    detail — concrete responsibilities, the working environment, and what success in the role looks like. 
                    Vary sentence length and avoid repeating the same opener. Do not pad with filler or restate the same point in 
                    different words.

                SKILLS FORMAT:
                - Skills should be a comma-separated list, e.g. "Communication, Teamwork, Problem Solving, Time Management"

                JSON FORMATTING RULE (important - broken JSON here means the whole extraction fails):
                - Return ONLY a single valid JSON object. No markdown fences, no commentary before or after.
                - Do NOT add any HTML style="..." attributes or any other attributes containing double quotes.
                Use plain HTML tags only (<p>, <ul>, <li>, <strong>, <table>, <tr>, <td>, etc.) with no attributes,
                so there is no risk of an unescaped quote breaking the JSON.
                - If you must quote something inside a string value, escape it as \\" - never leave a bare " inside
                a JSON string.

                IF THE CONTENT DOES NOT DESCRIBE A REAL JOB POSTING (e.g. an error page, login wall, empty page, or
                unrelated content), respond with ONLY this JSON and nothing else:
                {"error": true, "message": "No valid job posting was found in the provided content."}

                SMART DEFAULTS (apply ONLY when a field is completely missing from the source):
                - employment_type: "full-time"
                - deadline: "{$twoWeeksAhead}"
                - experience_level_name: "entry level" (only if that exact string exists in the Experience Levels list above, else null)
                - education_level_name: "Certificate" (only if that exact string exists in the Education Levels list above, else null)
                - location_type: "on-site"
                - For job_description/responsibilities/qualifications/skills that are completely absent: write
                professional, role-appropriate content based on the job_title (and company_name if known).

                FIELDS TO RETURN:
                {
                "job_title": "exact job title",
                "company_name": "exact company name as written in the content, else null - do NOT pick from a list",
                "job_description": "verbatim if present, else generated - plain HTML, no attributes, no contact info",
                "responsibilities": "MUST be HTML <ul><li> list format - NEVER plain text with dashes or commas",
                "qualifications": "MUST be HTML <ul><li> list format - NEVER plain text with dashes or commas",
                "skills": "comma-separated list",
                "application_procedure": "verbatim if present, else generated - plain HTML, no attributes, no raw link",
                "application_link": "raw application URL if one is present anywhere in the content, else null",
                "email": "contact email if mentioned, else null",
                "telephone": "phone number(s) if mentioned, comma-separated if more than one, else null",
                "deadline": "YYYY-MM-DD, from content if present else the default above",
                "duty_station": "office/work location address if mentioned, else null",
                "location_type": "remote|hybrid|on-site",
                "employment_type": "full-time|part-time|contract|internship|volunteer|temporary",
                "salary_amount": "numeric amount if mentioned, else null",
                "payment_period": "monthly|yearly|weekly|daily|hourly, else null",
                "currency": "currency code if mentioned, else null",
                "meta_description": "155-character SEO description generated from the job title/content",
                "keywords": "8-12 comma-separated search phrases people really type: the job title, title + 'jobs in' + town/district/country, 'vacancy', and close synonyms of the role. No repeats of the same phrase.",
                "experience_level_name": "must be an EXACT value from the Experience Levels list above, else null",
                "education_level_name": "must be an EXACT value from the Education Levels list above, else null",
                "industry_name": "must be an EXACT value from the Industries list above, else null",
                "category_name": "must be an EXACT value from the Job Categories list above, else null",
                "job_type_name": "must be an EXACT value from the Job Types list above, else null",
                "job_location_name": "must be an EXACT value from the Locations list above (match by district), else null",
                "salary_range_name": "must be an EXACT value from the Salary Ranges list above, else null",
                "country_code": "one of AU, UG, KE, TZ, RW, MW, ZM, SG if the job's country matches one of these, else null",
                "is_urgent": false,
                "is_featured": false,
                "is_resume_required": true,
                "is_cover_letter_required": false,
                "is_academic_documents_required": false,
                "is_application_required": false,
                "is_whatsapp_contact": false,
                "is_telephone_call": false,
                "work_hours": "work schedule if mentioned, else null"
                }

                CONTENT:
                ---
                {$content}
                ---
                PROMPT;
    }

    protected function buildImageExtractionPrompt(array $referenceData = []): string
    {
        $referenceBlock = $this->formatReferenceListsForPrompt($referenceData);

        return "Extract all job information visible in this image. Preserve the EXACT text as shown - "
            . "do not summarize or reword anything that is actually visible. Only generate content for "
            . "fields that are completely absent from the image.\n\n{$referenceBlock}\n\n"
            . "CRITICAL - RESPONSIBILITIES & QUALIFICATIONS MUST BE HTML LISTS:\n"
            . "When you generate or return responsibilities and qualifications, they MUST be formatted as HTML "
            . "unordered lists using <ul> and <li> tags. NEVER return them as plain text with dashes, asterisks, "
            . "numbers, commas, or periods as separators. ALWAYS use proper HTML list format.\n\n"
            . "CORRECT EXAMPLE: \"responsibilities\": \"<ul><li>Lead and manage key initiatives</li><li>Collaborate with cross-functional teams</li></ul>\"\n"
            . "INCORRECT EXAMPLE (DO NOT DO THIS): \"responsibilities\": \"Lead and manage key initiatives, Collaborate with cross-functional teams\"\n\n"
            . "Do NOT include emails, phone numbers, WhatsApp numbers, or application links inside "
            . "job_description, responsibilities, qualifications, or skills - put them only in the "
            . "dedicated email/telephone/application_link fields. If more than one contact number is "
            . "visible, list them all in telephone separated by commas (never with \"/\", \"and\", or \"or\"). "
            . "If an application URL is visible, put it in application_link (do not embed the raw link in "
            . "application_procedure). Do not write any warning about paying money in application_procedure, "
            . "one is added automatically. Any content you write rather than copy from the image must read as "
            . "natural, human-written, SEO-friendly copy.\n\n" . $this->humanWritingRules() . "\n\n"
            . "If the "
            . "resulting job_description would be under roughly 120 words, expand it with reasonable "
            . "role-appropriate detail so it's substantive enough for search engines. "
            . "Return ONLY a single valid JSON object with the same fields as a standard job extraction "
            . "(job_title, company_name, job_description, responsibilities, qualifications, skills, "
            . "application_procedure, application_link, email, telephone, deadline, duty_station, "
            . "location_type, employment_type, salary_amount, payment_period, currency, meta_description, "
            . "keywords, experience_level_name, education_level_name, industry_name, category_name, "
            . "job_type_name, job_location_name, salary_range_name, country_code, work_hours, and the is_* "
            . "boolean flags). For company_name, write it exactly as shown - it is NOT chosen from a list. "
            . "For every other *_name field, it MUST be an exact value from the lists above, else null. "
            . "Do NOT include any HTML attributes (no style=\"...\", no class=\"...\") - use plain tags "
            . "only, so nothing can break the JSON. If the image does not show a real job posting, return "
            . "{\"error\": true, \"message\": \"No job posting could be read from this image.\"}";
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

            CRITICAL - RESPONSIBILITIES & QUALIFICATIONS MUST BE HTML LISTS:
            Responsibilities and qualifications MUST be formatted as HTML unordered lists using <ul> and <li> tags.
            NEVER return them as plain text with dashes, asterisks, numbers, commas, or periods as separators.
            ALWAYS use proper HTML list format.
            
            CORRECT EXAMPLE for responsibilities:
            "<ul><li>Lead and manage key initiatives</li><li>Collaborate with cross-functional teams</li><li>Ensure timely delivery of projects</li></ul>"
            
            CORRECT EXAMPLE for qualifications:
            "<ul><li>Bachelor's degree in relevant field</li><li>5+ years of experience</li><li>Excellent communication skills</li></ul>"

            {$referenceBlock}

            {$humanRules}

            Naturally include the job title and relevant industry/skill/location keywords once or twice,
            never forced. The job_description should run roughly 120-180 words in 2-4 paragraphs of uneven
            length (one can be a single sentence). Do not follow a fixed paragraph recipe. Cover what the
            person actually does, who they work with and what the employer wants, in whatever order suits
            this particular job. Do not repeat the same idea in different words to hit the length.

            Return ONLY a valid JSON object - no explanation, no markdown, no code blocks. Do not use any HTML
            attributes (no style="...") on any tag - plain <p>/<ul>/<li> only, so nothing can break the JSON. Do not
            include any contact info or application links anywhere - this is a fresh generated posting.

            {
            "job_description": "2-4 paragraphs as HTML with <p> tags",
            "responsibilities": "HTML <ul><li> list format - NEVER plain text with dashes or commas",
            "qualifications": "HTML <ul><li> list format - NEVER plain text with dashes or commas",
            "skills": "comma-separated list of 6-10 relevant skills, specific to this job rather than generic soft skills",
            "meta_description": "155-character SEO meta description",
            "keywords": "comma-separated SEO keywords",
            "experience_level_name": "must be an EXACT value from the Experience Levels list above, else null",
            "education_level_name": "must be an EXACT value from the Education Levels list above, else null",
            "industry_name": "must be an EXACT value from the Industries list above, else null",
            "category_name": "must be an EXACT value from the Job Categories list above, else null",
            "job_type_name": "must be an EXACT value from the Job Types list above, else null",
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
        - Preserve the original meaning and any facts present; improve clarity and professionalism only.
        - {$humanRules}
        - Match the register to the field: descriptions and procedures should read as flowing
        paragraphs; responsibilities and qualifications should read as crisp, scannable lists.
        - Return ONLY the improved content as clean HTML using <p> and <ul><li> - no attributes on any tag.
        - Do NOT include explanations, markdown fences, or code blocks.
        - Do NOT wrap the response in JSON - return plain HTML text only.

        CURRENT CONTENT:
        {$content}
        PROMPT;
    }

    

    /**
     * Apply smart defaults - only fills fields that are genuinely missing.
     */
    protected function applySmartDefaults(array $data, ?string $country = null): array
    {
        $countrySettings = $country ? ($this->countrySettings[$country] ?? null) : null;
        $referenceData = $this->getReferenceData($country);

        foreach ([
            'industry_name'        => 'industries',
            'category_name'        => 'categories',
            'job_type_name'        => 'job_types',
            'job_location_name'    => 'locations',
            'experience_level_name'=> 'experience_levels',
            'education_level_name' => 'education_levels',
            'salary_range_name'    => 'salary_ranges',
        ] as $field => $refKey) {
            if (!empty($data[$field]) && !in_array($data[$field], $referenceData[$refKey], true)) {
                // Log::info("AI returned unmatched {$field}", ['value' => $data[$field]]);
                $data[$field] = null; // don't let a hallucinated name reach the dropdown-matcher
            }
        }

        $twoWeeksAhead = Carbon::now()->addWeeks(2)->format('Y-m-d');

        $data['job_title'] = $data['job_title'] ?? '';
        $data['company_name'] = $data['company_name'] ?? '';
        $data['deadline'] = $data['deadline'] ?: $twoWeeksAhead;
        $data['location_type'] = $data['location_type'] ?: 'on-site';
        $data['employment_type'] = $data['employment_type'] ?: 'full-time';
        $data['experience_level_name'] = $data['experience_level_name'] ?: 'entry level';
        $data['education_level_name'] = $data['education_level_name'] ?: 'Certificate';
        $data['currency'] = $data['currency'] ?: ($countrySettings['currency'] ?? 'AUD');
        $data['country_code'] = $data['country_code'] ?? $country;
        $data['is_resume_required'] = $data['is_resume_required'] ?? true;

        foreach ([
            'email', 'telephone', 'duty_station', 'salary_amount', 'payment_period',
            'industry_name', 'category_name', 'work_hours',
        ] as $optional) {
            $data[$optional] = $data[$optional] ?? null;
        }

        foreach ([
            'is_urgent', 'is_featured', 'is_cover_letter_required', 'is_academic_documents_required',
            'is_application_required', 'is_whatsapp_contact', 'is_telephone_call',
        ] as $flag) {
            $data[$flag] = $data[$flag] ?? false;
        }

        // Only generate fallback content when the field is genuinely empty AND we have a title to work from.
        if (empty(trim(strip_tags($data['job_description'] ?? ''))) && !empty($data['job_title'])) {
            $data['job_description'] = $this->generateFallbackDescription($data['job_title'], $data['company_name'] ?: null, $data['duty_station']);
        }
        if (empty(trim(strip_tags($data['responsibilities'] ?? ''))) && !empty($data['job_title'])) {
            $data['responsibilities'] = $this->generateFallbackResponsibilities($data['job_title'], $data['company_name'] ?: null);
        }
        if (empty(trim(strip_tags($data['qualifications'] ?? ''))) && !empty($data['job_title'])) {
            $data['qualifications'] = $this->generateFallbackQualifications($data['job_title']);
        }
        if (empty(trim($data['skills'] ?? '')) && !empty($data['job_title'])) {
            $data['skills'] = $this->generateFallbackSkills($data['job_title']);
        }
        if (empty(trim($data['meta_description'] ?? '')) && !empty($data['job_title'])) {
            $data['meta_description'] = $this->generateFallbackMetaDescription($data['job_title'], $data['company_name'] ?: null, $data['duty_station']);
        }
        if (empty(trim($data['keywords'] ?? '')) && !empty($data['job_title'])) {
            $data['keywords'] = $this->generateFallbackKeywords($data['job_title']);
        }
        $data['application_procedure'] = $data['application_procedure'] ?? '';

        // SEO safety net: even if job_description came back non-empty (verbatim from the
        // source, or from the block above), it might still be too thin (e.g. a source
        // page that only had a one-line title with no real description) to be useful for
        // search engines. Pad it out with additional role-appropriate detail rather than
        // discarding whatever real content was there.
        if (!empty($data['job_title'])) {
            $data['job_description'] = $this->ensureMinimumDescriptionLength(
                $data['job_description'] ?? '',
                $data['job_title'],
                $data['company_name'] ?: null,
                $data['duty_station']
            );
        }

        return $data;
    }

    /**
     * If job_description is under ~15 words it's too thin to help SEO, so this
     * supplements it with additional natural, role-appropriate detail instead of
     * leaving it (or replacing it outright and losing whatever real content
     * was present).
     */
    protected function ensureMinimumDescriptionLength(string $description, string $title, ?string $company, ?string $location): string
    {
        $wordCount = str_word_count(strip_tags($description));

        // 🔽 Raised from 15 → 120 (SEO-safe minimum without forcing bloat)
        if ($wordCount >= 120) {
            return $description;
        }

        $extra = $this->generateFallbackDescription($title, $company, $location, true);

        return trim(strip_tags($description)) !== ''
            ? $description . $extra
            : $extra;
    }

    protected function generateFallbackDescription(string $title, ?string $company, ?string $location, bool $supplement = false): string
    {
        $at    = $company  ? " at {$company}"  : '';
        $in    = $location ? " in {$location}" : '';
        $seed  = $title . '|' . $company . '|' . $location;
        $pick  = fn(array $pool, string $salt) => $pool[crc32($seed . $salt) % count($pool)];

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
            "The person we hire will handle the daily work of the role, keep records up to date and report to a supervisor. Some tasks are routine and some arrive without warning, so being organised matters more than any single skill.",
            "Most of the week is spent on the core duties of the post, with regular check-ins with colleagues from other departments. You should be able to plan your own time and finish work without being chased.",
            "This is a hands-on position. You will work closely with the rest of the team, share updates with your supervisor and deal with queries as they come up. Experience in a similar setting will help you settle in quickly.",
            "The work needs someone steady who can follow procedures but also speak up when something is not working. Clear writing and good communication with clients and co-workers are part of the job.",
            "You will join an existing team, so learning how things are already done will be your first task. After that, you will be expected to carry your own workload and suggest improvements where you see them.",
            "The role involves regular contact with colleagues and the public, careful record keeping and meeting deadlines set by management. Training on internal systems will be given where needed.",
        ];

        $closers = [
            "<p>Read the requirements below and apply if they match your background.</p>",
            "<p>Only candidates who meet the requirements listed below should apply.</p>",
            "<p>Please check the closing date and send your application in good time.</p>",
            "<p>Applicants should have the documents mentioned below ready before applying.</p>",
            "<p>Shortlisted candidates will be contacted for interviews.</p>",
            "",
        ];

        $body = '<p>' . $pick($bodies, 'b') . '</p>';
        $close = $pick($closers, 'c');

        // Used to top up a short real description: skip the opener so the
        // text doesn't start a second time halfway down the page.
        if ($supplement) {
            return $body . $close;
        }

        return $this->scrubAiTells($pick($openers, 'o') . $body . $close);
    }

    protected function generateFallbackResponsibilities(string $title, ?string $company): string
    {
        $seed = $title . '|' . $company;

        $pool = [
            "Handle the day-to-day tasks of the {$title} position",
            "Keep accurate records and update them on time",
            "Work with colleagues in other departments when a task needs more than one team",
            "Report to your supervisor on progress and any problems",
            "Respond to queries from clients, staff or the public",
            "Prepare short weekly and monthly reports",
            "Follow the organisation's policies and procedures",
            "Help train or guide new staff when asked",
            "Look after office or work-area materials and equipment",
            "Attend team meetings and follow up on agreed actions",
            "Suggest simple ways to improve how the work is done",
            "Carry out any other reasonable duties assigned by management",
        ];

        $items = $this->seededPick($pool, $seed, 5 + (crc32($seed . 'n') % 3));

        return '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>';
    }

    protected function generateFallbackQualifications(string $title): string
    {
        $pool = [
            "A certificate, diploma or degree relevant to the {$title} role",
            "Previous work experience in a similar position",
            "Good spoken and written English",
            "Basic computer skills, including Word and Excel",
            "Able to work with little supervision and meet deadlines",
            "Honest, punctual and well organised",
            "Good communication skills",
            "Willingness to work flexible hours when needed",
            "Ability to work well in a team",
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
        $at  = $company ? " at {$company}" : '';
        $in  = $location ? " in {$location}" : '';
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
            'locale' => 'en_US',
            'timezone' => 'UTC',
        ];
    }



    // CV Extraction

    


    /**
     * Order to try models in for CV extraction
     */
    protected array $cvFallbackOrder = ['cohere', 'gemini', 'openai', 'claude', 'grok', 'mistral'];

    /**
     * Extract structured CV data from raw resume text, trying each configured
     * AI model in order until one returns usable JSON.
     * 
     * Returns empty data structure instead of throwing on failure
     */
    public function extractCvData(string $resumeText): array
    {
        $prompt = $this->buildCvExtractionPrompt($resumeText);
        $lastException = null;
        $allFailed = true;

        // Get ordered models - default first, then fallbacks
        $models = $this->getOrderedCvModels();

        foreach ($models as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                // Log::info("⏭️ Skipping {$model} - API key not configured");
                continue;
            }

            try {
                // Log::info("🤖 Attempting CV extraction with {$model}");
                $raw = $this->callAiApi($model, $apiKey, $prompt, null, true);
                
                if ($raw) {
                    $data = $this->parseJsonResponse($raw);
                    
                    if ($data !== null && !empty($data) && $this->hasValidCvData($data)) {
                        // Log::info("✅ CV extraction succeeded with {$model}");
                        return $data;
                    }
                }

                Log::warning("⚠️ {$model} returned unparseable or empty response - trying next model");
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("⚠️ CV extraction failed with {$model}: " . $e->getMessage());
            }
        }

        // If we get here, all models failed
        Log::error('All AI models failed to extract CV data', [
            'last_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);
        
        // Return empty data structure instead of throwing
        return $this->getEmptyCvData();
    }

    /**
     * The order in which models are tried for all CV work.
     *
     * Design intent:
     *   gemini  → primary (fast, cheap, usually available)
     *   claude  → first fallback (better on long-form writing)
     *   openai  → second fallback (reliable structured output)
     *   cohere  → LAST RESORT (always tried, even if all the above fail)
     *
     * cohere is appended at the very end unconditionally, so a config change
     * can never accidentally remove it from the chain.
     */
    protected function getOrderedCvModels(): array
    {
        $ordered = ['gemini', 'claude', 'openai'];

        // Add optional middle-tier models if they are configured.
        foreach (['grok', 'mistral'] as $model) {
            if ($this->isModelEnabled($model)) {
                $ordered[] = $model;
            }
        }

        // Guaranteed final fallback.
        $ordered[] = 'cohere';

        return array_values(array_unique($ordered));
    }

    /**
     * Check if extracted data has any meaningful content
     */
    protected function hasValidCvData(array $data): bool
    {
        // Check if there's any non-empty field
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty(trim($value))) {
                return true;
            }
            if (is_array($value) && !empty($value)) {
                return true;
            }
            if (is_int($value) && $value > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get empty CV data structure
     */
    protected function getEmptyCvData(): array
    {
        return [
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'country' => null,
            'postal_code' => null,
            'date_of_birth' => null,
            'nationality' => null,
            'professional_summary' => null,
            'professional_title' => null,
            'years_of_experience' => null,
            'linkedin_url' => null,
            'github_url' => null,
            'portfolio_url' => null,
            'skills' => [],
            'languages' => [],
            'certifications' => [],
            'education' => [],
            'work_experience' => [],
            'projects' => [],
        ];
    }

    /**
     * Get API key for a model
     */
    protected function getApiKeyFor(string $model): ?string
    {
        return match ($model) {
            'openai' => env('OPENAI_API_KEY'),
            'claude' => env('ANTHROPIC_API_KEY'),
            'gemini' => env('GEMINI_API_KEY'),
            'grok' => env('GROK_API_KEY'),
            'cohere' => env('COHERE_API_KEY'),
            'mistral' => env('MISTRAL_API_KEY'),
            default => null,
        };
    }

    /**
     * Check if model is usable (has API key and is configured)
     */
    protected function isModelUsable(string $model, ?string $apiKey): bool
    {
        if (empty($apiKey) || str_contains($apiKey, 'xxxx') || str_contains($apiKey, 'your-')) {
            return false;
        }
        return (bool) $this->getModelConfig($model);
    }

    /**
     * Parse JSON response, handling various formats
     */
    protected function parseJsonResponse($raw): ?array
    {
        // If raw is an array, encode it
        if (is_array($raw)) {
            $raw = json_encode($raw);
        }
        
        // If raw is not a string, return null
        if (!is_string($raw)) {
            return null;
        }

        // Remove markdown code fences
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw));
        $cleaned = trim($cleaned);

        // Tolerates chatter around the object and raw line breaks inside strings
        $data = $this->decodeJsonLenient($cleaned);

        if (is_array($data)) {
            return $data;
        }

        Log::warning('JSON parse error', [
            'error' => json_last_error_msg(),
            'raw_preview' => substr($raw, 0, 500)
        ]);

        return null;
    }

    /**
     * Build CV extraction prompt
     */
    protected function buildCvExtractionPrompt(string $resumeText): string
    {
        return <<<PROMPT
        You are a strict data-extraction engine. Read the resume/CV text below and
        return ONLY a single valid JSON object - no markdown, no commentary, no
        code fences - matching EXACTLY this shape. Use null or an empty array for
        any field you cannot find. Never invent data that isn't in the text.

        {
        "first_name": string|null,
        "last_name": string|null,
        "phone": string|null,
        "address": string|null,
        "city": string|null,
        "country": string|null,
        "postal_code": string|null,
        "date_of_birth": "YYYY-MM-DD"|null,
        "nationality": string|null,
        "professional_summary": string|null,
        "professional_title": string|null,
        "years_of_experience": integer|null,
        "linkedin_url": string|null,
        "github_url": string|null,
        "portfolio_url": string|null,
        "skills": string[],
        "languages": string[],
        "certifications": [{"name": string, "issuer": string|null, "year": string|null}],
        "education": [{"institution": string, "degree": string|null, "field": string|null, "grade": string|null, "start_year": string|null, "end_year": string|null}],
        "work_experience": [{"company": string, "title": string, "start_date": string|null, "end_date": string|null, "description": string|null}],
        "projects": [{"name": string, "description": string|null, "url": string|null}]
        }

        For education, include a "grade" field (e.g., "A", "B+", "First Class", "Distinction", "Merit", "Pass", "GPA: 3.8/4.0", etc.) if mentioned in the CV, otherwise null.

        Resume text:
        ---
        {$resumeText}
        ---
        PROMPT;
    }

    /**
     * Call AI API for CV extraction
     */
    protected function callAiApiForCv(string $model, string $apiKey, string $prompt, ?string $imageBase64 = null): array|string
    {
        $config = $this->getModelConfig($model);
        if (!$config) {
            throw new \Exception("Model '{$model}' not configured.");
        }

        $endpoint = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $config['max_tokens'] ?? 4096;

        // Set headers based on model
        $headers = ['Content-Type' => 'application/json'];
        
        if ($model === 'openai' || $model === 'grok' || $model === 'mistral' || $model === 'cohere') {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key'] = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }

        $body = $this->buildCvRequestBody($model, $modelName, $maxTokens, $prompt, $imageBase64);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        $response = Http::timeout(60)
            ->withHeaders($headers)
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw new \Exception("API error ({$model}): " . $this->extractCvErrorMessage($response));
        }

        return $this->extractCvResponseText($model, $response->json());
    }

    /**
     * Build request body for CV extraction
     */
    protected function buildCvRequestBody(string $model, string $modelName, int $maxTokens, string $prompt, ?string $imageBase64): array
    {
        switch ($model) {
            case 'openai':
            case 'grok':
            case 'mistral':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                ];

            case 'claude':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ];

            case 'gemini':
                return [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => ['maxOutputTokens' => $maxTokens],
                ];

            case 'cohere':
                return [
                    'model' => $modelName,
                    'prompt' => $prompt,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.1,
                    'return_likelihoods' => 'NONE',
                ];

            default:
                throw new \Exception("Unsupported model: {$model}");
        }
    }

    /**
     * Extract error message from CV API response
     */
    protected function extractCvErrorMessage($response): string
    {
        $body = $response->body();
        $data = json_decode($body, true);

        $errorPaths = [
            'error.message',
            'error.error.message',
            'error.error',
            'message',
            'error'
        ];

        foreach ($errorPaths as $path) {
            $value = $this->getNestedValue($data, $path);
            if ($value && is_string($value)) {
                return $value;
            }
        }

        return $body;
    }

    /**
     * Extract text from CV API response
     */
    protected function extractCvResponseText(string $model, array $data): string
    {
        $text = match ($model) {
            'openai', 'grok', 'mistral' => $data['choices'][0]['message']['content'] ?? '',
            'claude' => $data['content'][0]['text'] ?? '',
            'gemini' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'cohere' => $data['generations'][0]['text'] ?? '',
            default => '',
        };

        if (empty($text)) {
            Log::warning("Empty response from AI model '{$model}'", ['response' => json_encode($data)]);
        }

        return $text;
    }

    /**
     * Get nested array value using dot notation
     */
    protected function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    
    /**
     * Run the free AI gap review on a CV.
     * Reuses getOrderedCvModels() / isModelUsable() / callAiApi() / parseJsonResponse().
     *
     * @throws \Exception  if all configured models fail
     */
    public function reviewCvGaps(string $resumeText, ?string $jobDescription = null): array
    {
        $prompt = $this->buildGapReviewPrompt($resumeText, $jobDescription);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                Log::info("Gap review: skipping {$model} (no API key)");
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if ($data !== null && !empty($data)) {
                    // Sanity check: the AI must return an object with at least one of the expected keys
                    if (isset($data['overall_score']) || isset($data['sections']) || isset($data['summary'])) {
                        Log::info("Gap review succeeded with {$model}");
                        return $this->normaliseGapReview($data);
                    }

                    Log::warning("Gap review: {$model} returned JSON but shape looks wrong", [
                        'keys' => array_keys($data),
                    ]);
                } else {
                    Log::warning("Gap review: {$model} returned unparseable/empty response");
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Gap review failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'CV gap review failed on all configured models. Last error: ' .
            ($lastError ?? 'no usable response from any model.')
        );
    }

    /**
     * "missing_fields" are the questions shown to the candidate, each written as
     * "Topic: question". Cleans what the model sent. If it returned an old-style key
     * (for example "gpa_scale"), it is swapped for a full, plain question.
     */
    protected function normaliseGapReview(array $data): array
    {
        $out  = [];
        $seen = [];

        foreach ((array) ($data['missing_fields'] ?? []) as $item) {
            $text = trim(preg_replace('/\s+/', ' ', (string) $item));
            $text = preg_replace('/^\d+[.)]\s*/', '', $text);   // the system adds numbers
            if ($text === '') {
                continue;
            }

            if (preg_match('/^[a-z0-9_]+$/i', $text)) {              // a bare key, not a question
                [$group, $question] = $this->fallbackGapQuestion(strtolower($text));
                $text = "{$group}: {$question}";
            } elseif (!str_contains($text, ':')) {
                $text = 'Details: ' . $text;
            }

            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = mb_substr($text, 0, 600);
        }

        $data['missing_fields'] = array_slice($out, 0, 15);
        unset($data['questions']);

        return $data;
    }

    /** Plain-language backup questions, used only when the model skipped a missing field. */
    protected function fallbackGapQuestion(string $field): array
    {
        $map = [
            'contact_email'                    => ['Contact', 'What is the best professional email address for employers to reach you? (For example firstname.lastname@gmail.com.)'],
            'contact_phone'                    => ['Contact', 'Which phone number should employers call or WhatsApp? Please give one or two numbers.'],
            'location_town_district'           => ['Contact', 'Which town or village and district do you live in now? Are you willing to work in other districts or to relocate?'],
            'linkedin_url'                     => ['Contact', 'Do you have a LinkedIn page? If yes, paste the link. If not, write "none".'],
            'target_job_title'                 => ['Your target job', 'Which job title do you want most, and which type of employer (government, NGO, private company)? Please send one or two adverts you would like to apply to.'],
            'professional_summary'             => ['Your target job', 'In two or three sentences, how would you describe your work to an employer: what you do, how long you have done it, and what you are best at?'],
            'years_of_experience'              => ['Your experience', 'In total, how many years and months of paid or volunteer work do you have in your field? List any breaks of more than three months and what you did during them.'],
            'professional_registration_number' => ['Registration and licence', 'What is your professional registration or enrolment number, and who issued it? When does your practising licence expire (day, month, year)?'],
            'registration_expiry_date'         => ['Registration and licence', 'On what date does your registration or practising licence expire?'],
            'employment_status'                => ['Your work history', 'For each job on your CV, were you paid staff, a volunteer, an intern or on placement? What was your exact job title?'],
            'job_title_exact'                  => ['Your work history', 'What exact job title appears on your appointment letter or contract for each job on your CV?'],
            'employment_start_end_dates'       => ['Your work history', 'For each job on your CV, in what month and year did you start and finish? Write "to date" if you still work there.'],
            'employment_gap_explanation'       => ['Your work history', 'What were you doing in the periods between your jobs (study, casual work, job search, family)? One short line for each gap is enough.'],
            'key_duties_for_role'              => ['Your work history', 'Tell us the real duties you carried out in your most recent job. List everything you actually did, and leave out what you only observed.'],
            'quantified_results'               => ['Your results', 'Give numbers for your best work: how many clients, patients, customers, interviews, households or transactions did you handle per day, month or in total?'],
            'team_size_managed'                => ['Your results', 'Did you supervise anyone? If yes, how many people, and what did you do for them (training, targets, reports)?'],
            'budget_or_volume_handled'         => ['Your results', 'Did you handle money, stock or a budget in any job? Roughly how much per day, month or project (for example UGX 6 million per day)?'],
            'achievements'                     => ['Your results', 'What are you proud of at work? Think of targets you beat, problems you solved or processes you improved, with a number if you have one.'],
            'awards_or_recognition'            => ['Your results', 'Have you received any award, praise, promotion or extra responsibility? Who gave it and when?'],
            'promotions_or_added_responsibility'=> ['Your results', 'Were you ever given extra responsibilities or promoted in a job? What was added and in which month and year?'],
            'education_completion_dates'       => ['Education', 'In what month and year did you complete each qualification on your CV?'],
            'education_grades'                 => ['Education', 'What was your final grade, class or GPA for each qualification (with the scale, for example 4.2 out of 5)? For O-level and A-level, give the division or points.'],
            'gpa_scale'                        => ['Education', 'Your CV shows a GPA without a scale. Is it out of 4.0 or out of 5.0?'],
            'exam_board_results'               => ['Education', 'Did you pass the national or professional exams for your course (for example UNMEB, UBTEB, ICPAU)? Please give the year and your result.'],
            'certifications'                   => ['Training and certificates', 'Which professional certificates do you hold? For each one, say who issued it and in which year, and whether you have the certificate copy.'],
            'training_provider_and_year'       => ['Training and certificates', 'For each short course or training on your CV, who organised it, where was it, what year and how long did it last (days or weeks)?'],
            'clinical_or_field_placements'     => ['Training and certificates', 'Did you do placements or attachments during your course? Where, for how long, and what did you do there?'],
            'projects'                         => ['Your results', 'Tell us about one or two projects you worked on: what the goal was, what you did yourself and what the result was.'],
            'software_and_tools'               => ['Skills and tools', 'Which computer programs or tools do you use for work (for example Word, Excel, QuickBooks, KoboToolbox, DHIS2)? How well: basic, good or advanced?'],
            'technical_skills'                 => ['Skills and tools', 'Which practical skills of your profession can you do without help? List the ones you do most often.'],
            'industry_keywords'                => ['Skills and tools', 'Which procedures, systems or methods are used in the jobs you want? List the ones you have really used.'],
            'languages_with_levels'            => ['Skills and tools', 'Which languages do you speak, and how well in each: fluent, good or basic?'],
            'volunteer_work'                   => ['Your work history', 'Do you have any volunteer or community work? Please give the organisation, dates and what you did.'],
            'referees_full_details'            => ['Referees', 'Please give two or three referees with full name, job title, organisation, phone, email and how they know you. At least one should know your work well.'],
            'referee_consent'                  => ['Referees', 'Have all your referees agreed to be contacted by employers?'],
        ];

        if (isset($map[$field])) {
            return $map[$field];
        }

        $label = trim(str_replace('_', ' ', $field));

        return ['Other details', "Please tell us about your {$label}. Give names, dates and numbers where you can."];
    }

    protected function buildGapReviewPrompt(string $resumeText, ?string $jobDescription = null): string
    {
        $today = Carbon::now()->format('j F Y');

        $jobContext = $jobDescription
            ? "The candidate is targeting this specific job:\n\n{$jobDescription}\n\nEvaluate the CV against that role. Check every duty, skill, qualification and keyword the advert asks for, and flag the ones the CV does not show or cannot prove."
            : "No target job was given. Evaluate this CV for general professional quality and ATS readiness. Infer the career field from the CV and judge it against what recruiters in that field (especially in East Africa) expect to see.";

        return <<<PROMPT
            You are a senior recruiter and ATS (applicant tracking system) specialist with 10+ years of hiring
            experience. Your review is used to decide which questions the candidate must answer so their CV can be
            rebuilt to ATS standards. So do not give a general opinion: dig for the exact facts that are missing,
            unclear, inconsistent or unproven, because each one becomes a question to the candidate.

            TODAY'S DATE: {$today}. Use it to check whether "years of experience" claims and employment dates are
            still correct, and to spot open-ended or stale dates.

            {$jobContext}

            WHAT TO CHECK IN EACH SECTION
            1. Contact Information: full name, one reachable phone number, a professional email (flag joke or
               unprofessional addresses), town/village AND district/country (a bare district is too vague), and a
               LinkedIn or portfolio link where the field expects one. Note personal data that adds nothing and can
               cause bias (date of birth, photo, marital status, religion, hobbies, signed declaration).
            2. Professional Summary: is there one? Does it name the target role, the real years of experience (check
               against the dates), the main field and 2-3 provable strengths? Flag vague lines like "hard-working
               person seeking opportunities" and any claim that the dates do not support.
            3. Work Experience: for EVERY job check employer, exact job title, employment status (paid, volunteer,
               internship, placement, contract), start and end month and year, and unexplained gaps or very short
               stints. Check that the duties actually match the job title and the target role (duties copied from a
               different profession are a red flag). Look for contradictions, such as the same employer and date listed
               twice under different headings. Check that bullets start with action verbs and carry numbers: volume,
               clients, money handled, team size, targets, percentages, time saved, results. If numbers are absent,
               say which ones to ask for.
            4. Education: for each qualification check institution, exact award title, month and year of completion,
               grade, class, division or GPA (with its scale) and the exam board where relevant. Flag missing grades,
               unexplained gaps between levels, and professional registration or licensing that the field legally
               requires (for example nursing and midwifery council enrolment and practising licence, engineering,
               accountancy, law, teaching or medical registration). The registration number and expiry date are among
               the first things an employer checks.
            5. Skills: separate hard skills, tools and software, certifications and soft skills. Flag skill lists that
               are mostly soft skills, are generic, or lack the keywords this field and role normally list. Check for
               short courses and training (provider, year, duration, certificate), languages with levels, and software
               the candidate probably uses but did not name.

            ATS FORMAT PROBLEMS (mention the most serious in the "summary", or in the closest section note)
            - The CV looks like a photograph or scan, or is built from tables, text boxes, columns, headers or
              footers, graphics or rating bars. ATS cannot read these reliably.
            - Dates not in month-and-year form, missing job titles, headings with unusual names.
            - Inconsistent spelling of the candidate's name or employers.

            SCORING (overall_score, 0-100). Be strict and honest.
            - 85-100: complete, consistent, measurable results, and ATS-readable. Rare.
            - 65-84: solid, with some missing numbers, grades or keywords.
            - 40-64: key facts missing or duties generic. Needs a real interview with the candidate.
            - 0-39: major gaps, contradictions or unreadable content.
            Lower the score for each contradiction, missing registration, missing dates, and absence of numbers.

            Return ONLY a single valid JSON object. No markdown fences, no commentary before or after.

            {
            "overall_score": 0-100,
            "summary": "2-3 sentence plain-English overview naming the 2 biggest weaknesses by name and the single strongest point",
            "sections": [
                {
                    "section": "Contact Information",
                    "status": "ok" | "weak" | "missing",
                    "note": "one or two sentences naming exactly what is missing or wrong, quoting the CV where possible"
                },
                {
                    "section": "Professional Summary",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Work Experience",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Education",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Skills",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                }
            ],
            "missing_fields": [
                "Topic: one complete question written to this person about their CV",
                "Topic: another complete question"
            ]
            }

            HOW TO WRITE "missing_fields":
            Each item is one question that will be shown to the candidate as a form field, so it must stand on its
            own and be fully understood by someone who has not read any review. A short label such as "Training
            Provider And Year" is useless: even the owner of the CV would not know what to write. Write long,
            direct, specific questions.
            - Format of every item: "Topic: question". The topic is one to three words (a job, a school, "Target",
              "Education", "Referees", "Contact", "Availability"). After the colon comes the question. Do not number
              the items, the system numbers them.
            - Each question is 20 to 60 words and chains the 2 to 4 closely related things the expert needs for
              that topic, so the person can answer in one go.
            - Use the CV's own details: employer, job title, school, project and dates. Name them, so the person
              knows exactly which part of their CV you mean.
            - Say what form each answer should take (month and year, a number, yes or no, a short list) and give a
              short example in brackets where it helps. Estimates are fine ("about how many").
            - Ask about contradictions directly, for example the same job listed twice, a job shown as paid in one
              place and volunteer in another, or duty lines copied word for word across several roles.
            - Do not ask for anything the CV already states. Do not ask for date of birth, marital status,
              religion, a photo or an ID number; if the CV contains them, say in the Contact note that they should
              be removed.
            - Plain, friendly English in the second person ("you", "your"). No jargon, and never the word "ATS".
            - Put the most important topics first. Return 8 to 14 items, one topic each, with at most 15. Return an
              empty array [] only if truly nothing is missing.

            Examples of the right length and style (invented people, for style only):
            - "Target: which job, or type of job, are you applying for, and in which type of organisation (government, NGO or private)? Please send the advert if you have one."
            - "Education: in what month and year did you finish your Diploma in Business Administration at Makerere, and what was your final grade or GPA (and the scale, for example 4.2 out of 5)? Do you also have a degree, and what were your UCE and UACE years and schools?"
            - "Work at Total Energies: what was your exact job title, at which station, and what were your start and end dates (month and year)? Roughly how much cash did you reconcile per day, and how many staff did you supervise?"
            - "Field surveys: your duties are word for word the same under five projects. For each project, about how many households or respondents did you survey, how many days per week did you work, and did you train or lead anyone?"
            - "Referees: which three people should we keep as referees, and what are their job titles, phone numbers and emails? Do they know you have listed them?"

            RULES:
            - Use EXACTLY the 5 sections listed above, in that order. Do not add or remove sections.
            - "status" must be one of: "ok", "weak", "missing" (lowercase). Use "ok" only when nothing important is
              missing. Use "missing" only when the section is absent.
            - Every "note" must be specific to THIS CV: name the job, school, date or line concerned. Never write
              generic advice such as "add more detail" or "improve formatting".
            - Do NOT invent details that are not in the CV, and do not assume a registration, grade or number exists.
            - Do NOT wrap the response in markdown code fences.
            - Do NOT include any text before or after the JSON object.

            CV text:
            ---
            {$resumeText}
            ---
            PROMPT;
    }

    /**
     * Screen a single CV against a job description.
     *
     * @return array{
     *     score: int,
     *     recommendation: string,
     *     summary: string,
     *     strengths: array,
     *     gaps: array,
     *     red_flags: array
     * }
     */
    public function screenApplicant(string $cvText, array $jobContext): array
    {
        $prompt = $this->buildScreeningPrompt($cvText, $jobContext);

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw  = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if (is_array($data) && isset($data['score'])) {
                    return [
                        'score'          => max(0, min(100, (int) $data['score'])),
                        'recommendation' => in_array($data['recommendation'] ?? '', ['strong_yes', 'maybe', 'no'], true)
                            ? $data['recommendation']
                            : 'maybe',
                        'summary'   => (string) ($data['summary'] ?? ''),
                        'strengths' => is_array($data['strengths'] ?? null) ? array_values(array_filter($data['strengths'])) : [],
                        'gaps'      => is_array($data['gaps'] ?? null) ? array_values(array_filter($data['gaps'])) : [],
                        'red_flags' => is_array($data['red_flags'] ?? null) ? array_values(array_filter($data['red_flags'])) : [],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Screening failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception('AI screening failed on all configured models.');
    }

    protected function buildScreeningPrompt(string $cvText, array $jobContext): string
    {
        $jobTitle       = $jobContext['job_title'] ?? 'this role';
        $jobDescription = strip_tags($jobContext['job_description'] ?? '');
        $responsibilities = strip_tags($jobContext['responsibilities'] ?? '');
        $qualifications   = strip_tags($jobContext['qualifications'] ?? '');
        $skills           = $jobContext['skills'] ?? '';

        return <<<PROMPT
            You are an experienced HR screener evaluating a candidate's CV against a specific job opening.

            JOB TITLE: {$jobTitle}

            JOB DESCRIPTION:
            {$jobDescription}

            RESPONSIBILITIES:
            {$responsibilities}

            REQUIRED QUALIFICATIONS:
            {$qualifications}

            REQUIRED SKILLS:
            {$skills}

            CANDIDATE'S CV:
            ---
            {$cvText}
            ---

            Your task: score how well this candidate matches this specific role.

            SCORING GUIDELINES (0-100):
            - 85-100 → Excellent match. Meets all key requirements. Strong evidence in CV.
            - 70-84  → Good match. Meets most requirements. Minor gaps.
            - 55-69  → Reasonable match. Meets some requirements. Notable gaps.
            - 40-54  → Weak match. Meets a few requirements. Significant gaps.
            - 0-39   → Poor match. Doesn't meet core requirements.

            BE FAIR AND OBJECTIVE:
            - Do NOT penalise candidates for name, gender, nationality, age, religion, or other protected characteristics.
            - Focus ONLY on skills, experience, education, and qualifications relevant to the role.
            - If the CV is sparse, judge it as weak but don't invent facts.
            - If the CV is strong but unorganised, still score it fairly.

            RED FLAGS are serious concerns only: e.g., no relevant experience at all for a senior role, missing critical certifications, glaring inconsistencies. Do NOT call minor formatting issues a red flag.

            Return ONLY this JSON object (no markdown, no commentary):

            {
            "score": 0-100,
            "recommendation": "strong_yes" | "maybe" | "no",
            "summary": "2-3 sentence verdict explaining the score",
            "strengths": ["specific strength from the CV", "..."],
            "gaps": ["specific missing skill or qualification", "..."],
            "red_flags": ["serious concern if any", "..."]
            }

            recommendation rules:
            - "strong_yes" → score >= 80
            - "maybe"      → score 50-79
            - "no"         → score < 50

            Keep strengths, gaps, and red_flags arrays to 3-5 items each. Be specific and factual — quote from the CV where useful.
            PROMPT;
    }



    /**
     * Generate a professional cover or application letter.
     *
     * Returns clean HTML (only <p> tags, no attributes).
     *
     * @param  string  $cvText        Raw text extracted from the candidate's CV
     * @param  array   $jobContext    ['job_title', 'company_name', 'job_description']
     * @param  string  $letterType    'cover' | 'application'
     * @throws \Exception             when every configured model fails
     */
    public function generateLetter(string $cvText, array $jobContext, string $letterType = 'cover'): string
    {
        $letterType = in_array($letterType, ['cover', 'application'], true) ? $letterType : 'cover';
        $prompt = $this->buildLetterPrompt($cvText, $jobContext, $letterType);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt);

                if (!is_string($raw) || trim($raw) === '') {
                    Log::warning("Letter generation: empty response from {$model}");
                    continue;
                }

                $clean = $this->cleanLetterHtml($raw);

                if (str_word_count(strip_tags($clean)) < 60) {
                    Log::warning("Letter generation: {$model} returned too short a letter", [
                        'word_count' => str_word_count(strip_tags($clean)),
                    ]);
                    continue;
                }

                return $clean;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Letter generation failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'Letter generation failed on all configured models. Last error: '
            . ($lastError ?? 'no usable response from any model.')
        );
    }

    protected function buildLetterPrompt(string $cvText, array $jobContext, string $letterType): string
    {
        $jobTitle      = $jobContext['job_title']       ?? 'the advertised role';
        $companyName   = $jobContext['company_name']    ?? 'the company';
        $jobDescription= strip_tags($jobContext['job_description'] ?? '');
        $jobDescription= $jobDescription !== '' ? $jobDescription : '(no detailed description provided)';

        $letterWord = $letterType === 'application' ? 'application letter' : 'cover letter';

        return <<<PROMPT
            You are a professional career writer with 15 years of experience helping candidates win interviews.

            TASK: Write a {$letterWord} for the candidate below, addressed to the target role.

            TARGET ROLE:
            Job Title: {$jobTitle}
            Company: {$companyName}
            Job Description / Context:
            {$jobDescription}

            CANDIDATE CV (raw text extracted from their CV):
            ---
            {$cvText}
            ---

            HARD RULES:
            - Length: 250 to 350 words. Never shorter, never much longer.
            - Tone: formal, professional, confident. Natural, human-written English. No AI-sounding filler.
            - Structure:
                1. Opening paragraph: state the role being applied for and where it was seen (if relevant), and one sentence on why the candidate is a strong fit.
                2. Body paragraph 1: highlight the most relevant experience from the CV, tied directly to the role.
                3. Body paragraph 2: highlight the most relevant skills, education, or achievements from the CV that match the role.
                4. Closing paragraph: express enthusiasm, thank the reader, and request an interview.
            - Use ONLY facts present in the CV. Do NOT invent employers, dates, degrees, or achievements.
            - If the CV is thin, write a strong but honest letter that focuses on transferable skills, not fabricated experience.
            - Address it generically ("Dear Hiring Manager,") unless the CV or job context names a specific person.
            - Do NOT include the candidate's address, the date, or the company's address at the top. Just the salutation, body, and sign-off.
            - End with "Yours sincerely," followed by the candidate's full name as it appears in the CV.
            - Do NOT include placeholder text like [Your Name] or [Company].
            - Return clean HTML using only <p> tags. No attributes on any tag. No <html>, <head>, or <body> wrapper.
            - Do NOT wrap the response in markdown code fences. Do NOT include any commentary before or after the letter.

            Return the letter now.
            PROMPT;
    }

    /**
     * Strip markdown fences, wrapper tags, and any stray attributes from the
     * AI's response so what we store is pure, safe HTML.
     */
    protected function cleanLetterHtml(string $raw): string
    {
        $clean = preg_replace('/^```(?:html|HTML)?\s*/', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = trim($clean);

        // Remove any <html>/<head>/<body> wrappers
        $clean = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $clean);

        // Strip attributes from every tag (style, class, id, etc.) so nothing can break layout
        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr', 'img'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        // Normalize paragraph spacing
        $clean = preg_replace('#<p>\s*</p>#i', '', $clean);

        // If the model returned plain text (no tags), wrap each blank-line-separated
        // block in <p> so we always store HTML.
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
     * Screen a single uploaded CV against a job description.
     *
     * Returns the match score, verdict, reasons, and extracted candidate
     * identity fields so the shortlist table can show real names.
     */
    public function screenUploadedCv(string $cvText, array $jobContext): array
    {
        $prompt = $this->buildUploadedScreeningPrompt($cvText, $jobContext);

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) continue;

            try {
                $raw  = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if (is_array($data) && isset($data['score'])) {
                    return [
                        'score'               => max(0, min(100, (int) $data['score'])),
                        'recommendation'      => in_array($data['recommendation'] ?? '', ['strong_yes', 'maybe', 'no'], true)
                                                    ? $data['recommendation']
                                                    : 'maybe',
                        'summary'             => (string) ($data['summary'] ?? ''),
                        'strengths'           => (array) ($data['strengths'] ?? []),
                        'gaps'                => (array) ($data['gaps'] ?? []),
                        'red_flags'           => (array) ($data['red_flags'] ?? []),
                        'matched_skills'      => (array) ($data['matched_skills'] ?? []),
                        'missing_skills'      => (array) ($data['missing_skills'] ?? []),
                        'candidate_name'      => $data['candidate_name']      ?? null,
                        'candidate_email'     => $data['candidate_email']     ?? null,
                        'candidate_phone'     => $data['candidate_phone']     ?? null,
                        'current_title'       => $data['current_title']       ?? null,
                        'years_of_experience' => isset($data['years_of_experience'])
                                                    ? (int) $data['years_of_experience']
                                                    : null,
                        'highest_education'   => $data['highest_education']   ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Uploaded CV screening failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception('AI screening failed on all configured models.');
    }

    protected function buildUploadedScreeningPrompt(string $cvText, array $jobContext): string
    {
        $title = $jobContext['job_title'] ?? 'this role';
        $jd    = strip_tags($jobContext['job_description'] ?? '');

        return <<<PROMPT
            You are an experienced HR screener evaluating one CV against a job.

            JOB TITLE: {$title}

            JOB DESCRIPTION:
            {$jd}

            CANDIDATE CV:
            ---
            {$cvText}
            ---

            TASK:
            1. Extract the candidate's identity from the CV.
            2. Score how well the CV matches the job (0-100).
            3. Explain the score with concrete strengths, gaps, red flags,
            and a skill-by-skill comparison.

            SCORING:
            - 85-100  Excellent match. Meets all core requirements with strong evidence.
            - 70-84   Good match. Meets most requirements with minor gaps.
            - 55-69   Reasonable match. Meets some requirements, notable gaps.
            - 40-54   Weak match. Meets a few requirements, significant gaps.
            - 0-39    Poor match. Does not meet core requirements.

            RULES:
            - Score only on skills, experience, education, and qualifications.
            - Do NOT score on name, gender, nationality, age, or other protected traits.
            - If the CV is sparse, mark it weak but do not invent facts.
            - Red flags are only serious concerns: wrong domain entirely, missing a required license, or a glaring inconsistency.

            Return ONLY this JSON object (no markdown, no commentary):

            {
            "candidate_name": "Full name as written on the CV, or null",
            "candidate_email": "Email from the CV, or null",
            "candidate_phone": "Phone from the CV, or null",
            "current_title": "Their most recent job title, or null",
            "years_of_experience": <integer or null>,
            "highest_education": "e.g. Bachelor's, Diploma, MSCE, or null",
            "score": <0-100>,
            "recommendation": "strong_yes" | "maybe" | "no",
            "summary": "2-3 sentence verdict explaining the score",
            "strengths": ["specific strength from the CV", "..."],
            "gaps": ["specific missing skill or qualification", "..."],
            "red_flags": ["serious concern if any"],
            "matched_skills": ["skill required by the job and present in the CV"],
            "missing_skills": ["skill required by the job and missing from the CV"]
            }

            Recommendation rule:
            - "strong_yes" -> score >= 80
            - "maybe"      -> score 50-79
            - "no"         -> score < 50

            Keep strengths, gaps, and red_flags to 3-5 items each. Be specific — quote the CV where useful.
            PROMPT;
    }

    /**
     * Tailor a CV to a specific job description.
     *
     * Returns clean HTML (only <p>, <ul>, <li>, <strong> tags, no attributes).
     */
    public function tailorCv(string $cvText, array $jobContext): string
    {
        $prompt = $this->buildTailorCvPrompt($cvText, $jobContext);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);
            if (!$this->isModelUsable($model, $apiKey)) continue;

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt);

                if (!is_string($raw) || trim($raw) === '') {
                    Log::warning("CV tailoring: empty response from {$model}");
                    continue;
                }

                $clean = $this->cleanCvHtml($raw);

                if (str_word_count(strip_tags($clean)) < 120) {
                    Log::warning("CV tailoring: too short from {$model}", [
                        'word_count' => str_word_count(strip_tags($clean)),
                    ]);
                    continue;
                }

                return $clean;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("CV tailoring failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'CV tailoring failed on all models. Last error: ' . ($lastError ?? 'none')
        );
    }

    protected function buildTailorCvPrompt(string $cvText, array $jobContext): string
    {
        $title       = $jobContext['job_title']       ?? 'this role';
        $company     = $jobContext['company_name']    ?? 'the company';
        $description = strip_tags($jobContext['job_description'] ?? '');
        $skills      = $jobContext['skills']          ?? '';

        return <<<PROMPT
            You are an expert CV writer. Your task is to produce a tailored CV that
            positions the candidate as a strong match for the specific job below.

            TARGET ROLE:
            Job Title: {$title}
            Company: {$company}
            Description: {$description}
            Required Skills: {$skills}

            ORIGINAL CV TEXT:
            ---
            {$cvText}
            ---

            RULES:
            - Never invent employers, dates, degrees, or achievements. Only reframe,
            reorder, and rewrite what is already in the CV.
            - Reorder sections so the most relevant experience is first.
            - Rewrite bullet points to emphasize the skills and outcomes the job needs.
            - Add a short professional summary (3-4 sentences) tailored to this role.
            - Add a "Key Skills" section listing the top 8-12 skills most relevant to
            the job, only from skills already present in the CV.
            - Use clean HTML: <h2>, <h3>, <p>, <ul>, <li>, <strong>.
            No attributes on any tag. No <style>, no <div>, no classes.
            - Do NOT include the candidate's address, date, or a letter.
            - Do NOT include a cover letter. The cover letter is generated separately.
            - Aim for 350-600 words.
            - Return ONLY the HTML. No markdown, no code fences, no commentary.

            Return the tailored CV HTML now.
            PROMPT;
    }

    protected function cleanCvHtml(string $raw): string
    {
        $clean = preg_replace('/^```(?:html|HTML)?\s*/', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $clean);

        // Strip attributes from all tags
        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        return trim($clean);
    }

    
}