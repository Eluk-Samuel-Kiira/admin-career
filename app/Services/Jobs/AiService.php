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
    protected const THIN_POST_WORDS            = 400;   // under this: AI enriches. At or above: AI preserves
    protected const ENRICH_TARGET_MIN_WORDS    = 450;   // enriched description target
    protected const ENRICH_TARGET_MAX_WORDS    = 650;
    protected const ENRICH_MIN_WORDS           = 400;   // reply below this is rejected and retried
    protected const EXTRACTION_TIMEOUT_SECONDS = 180;

    protected const GENERATED_DESC_MAX_CHARS = 500;
    protected const GENERATED_DESC_MIN_CHARS = 150;

    protected const LISTING_MIN_WORDS = 300;  // AI listing shorter than this is rejected
    protected const CONTENT_FIELDS = ['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'];

    protected const PER_MODEL_TIMEOUT_SECONDS = 90;   // one model never holds the request longer than this
    protected const CHAIN_DEADLINE_SECONDS    = 240;  // the whole fallback chain gives up after this
    protected const MIN_ATTEMPT_SECONDS       = 15;   // don't start a new model with less time than this left
    protected const STRIP_ABOUT_US            = true; // drop the employer "ABOUT US" marketing block before sending


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
        if (!$config || empty($config['api_key'])) {
            return false;
        }

        // A key like "sk-proj-xxxx" or "your-api-key" is a placeholder, not a real key.
        $key = (string) $config['api_key'];
        if (stripos($key, 'xxxx') !== false || stripos($key, 'your-') !== false || strlen($key) < 20) {
            return false;
        }

        return true;
    }

    /**
     * A model that just answered 401/403 (bad key) or 429 (quota used up) will answer
     * the same for the next few minutes. Skipping it saves a wasted round trip on every
     * request instead of trying it first every time.
     */
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
            // cooldown is an optimisation only
        }
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
    public function extractJobData(string $content, string $sourceType = 'text', ?string $model = null, ?string $country = null, ?string $position = null): array
    {
        $model     = $model ?? $this->defaultModel;
        $sourceUrl = null;

        $this->allowLongRun(900);

        if ($sourceType === 'url') {
            $sourceUrl = $content;
            $content   = $this->fetchUrlContent($content);
        }

        $content = $this->stripPageFurniture($content);

        $referenceData = $this->getReferenceData($country);
        $sourceWords   = $this->countWords($content);
        $mode          = $sourceWords < self::THIN_POST_WORDS ? 'enrich' : 'preserve';

        $prompt = $this->buildExtractionPrompt($content, $sourceType, $referenceData, $mode, $position, $sourceWords);

        $result = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt, $sourceWords, $mode) {
            $attemptPrompt = $prompt;

            for ($try = 1; $try <= 2; $try++) {
                $raw = $this->callAiApi($m, $key, $attemptPrompt, null, true);

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

                try {
                    $this->checkExtractionCompleteness($data, $sourceWords, $mode);
                    return $data;
                } catch (\Exception $e) {
                    // Only a "summarised it" reply is worth one more try with the same model
                    if ($try === 2 || !str_contains($e->getMessage(), 'cut short')) {
                        throw $e;
                    }

                    Log::info("AI model '{$m}' shortened the advert, retrying once", ['detail' => $e->getMessage()]);

                    $attemptPrompt = $prompt
                        . "\n\nCORRECTION: your previous reply was rejected. " . $e->getMessage()
                        . " Do it again. Copy EVERY paragraph, heading and bullet of the position word for word."
                        . " Do not summarise, merge, shorten or skip any bullet.";
                }
            }

            throw new \Exception('Reply cut short after a retry.');
        }, expectJson: true);

        if (!empty($result['error'])) {
            throw new \Exception($result['message'] ?? 'No valid job posting could be found in the provided content.');
        }

        $result = $this->coerceModelOutput($result);
        $result = $this->applySmartDefaults($result, $country);
        $result = $this->applyApplicationLinkHandling($result, $sourceUrl);

        return $this->applyGeorgiaFontToContentFields($result);
    }

    /**
     * Removes text that is never part of the job (menus, buttons, tracking lines, the employer's
     * generic "ABOUT US" block). Less text in means fewer tokens to read and, in preserve mode,
     * far fewer tokens to type back out. Job Info (posting date, "Apply Before") is kept so the
     * deadline can still be found.
     */
    protected function stripPageFurniture(string $text): string
    {
        $lines = preg_split('/\R/u', $text);
        $out   = [];
        $skipAbout = false;

        $furniture = '/^\s*(skip to main content\.?|view more jobs|apply now|apply to position|share|print|back to (search|results)|page .* careers loaded)\s*$/i';

        foreach ($lines as $line) {
            $t = trim($line);

            if (self::STRIP_ABOUT_US) {
                if (preg_match('/^(about us|about the company|about mtn.*)$/i', $t)) {
                    $skipAbout = true;
                    continue;
                }
                // the block ends where the advert's own closing lines start
                if ($skipAbout && preg_match('/^(female applicants|note:|apply now|job info|how to apply)/i', $t)) {
                    $skipAbout = false;
                }
                if ($skipAbout) {
                    continue;
                }
            }

            if ($t === '' && (end($out) === '' || $out === [])) {
                continue;                                   // collapse blank runs
            }
            if (preg_match($furniture, $t)) {
                continue;
            }
            if (preg_match('#^https?://\S+$#i', $t)) {
                continue;                                   // a line that is only a URL
            }

            $out[] = $t;
        }

        $clean = trim(implode("\n", $out));

        // Never let cleaning remove most of the advert. Fall back to the original.
        return $this->countWords($clean) >= max(40, (int) ($this->countWords($text) * 0.4)) ? $clean : $text;
    }

        /**
     * Rejects a reply that is too short or incomplete. Used inside the fallback loop,
     * so the next model is tried instead of saving a truncated advert.
     */
    protected function checkExtractionCompleteness(array $d, int $sourceWords, string $mode): void
    {
        if (empty($d['job_title'])) {
            throw new \Exception('AI reply is missing job_title.');
        }

        // Short adverts are topped up elsewhere, never rejected here
        if ($mode === 'enrich') {
            return;
        }

        if (empty($d['job_description'])) {
            throw new \Exception('AI reply is missing job_description.');
        }

        // When the advert lists several positions, only one is extracted on purpose,
        // so a much smaller share of the source words is normal.
        $multiPosition = !empty($d['other_positions']) && is_array($d['other_positions']);
        $ratio         = $multiPosition ? 0.25 : 0.6;

        $written = $this->countWordsInFields($d, self::CONTENT_FIELDS);
        if ($written < $sourceWords * $ratio) {
            throw new \Exception("Reply cut short: kept {$written} of {$sourceWords} words.");
        }
    }

    protected function countWordsInFields(array $d, array $fields): int
    {
        $total = 0;
        foreach ($fields as $f) {
            $v = $d[$f] ?? '';
            $total += $this->countWords(is_array($v) ? implode(' ', $v) : (string) $v);
        }
        return $total;
    }

    protected function countWords(string $text): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));

        return $plain === '' ? 0 : count(explode(' ', $plain));
    }


    /**
     * Trim a description we generated to at most $max characters of plain text, ending on a
     * whole sentence. Text that is already short enough is returned untouched (formatting kept).
     */
    protected function capGeneratedDescription(string $html, int $max = self::GENERATED_DESC_MAX_CHARS): string
    {
        $plainOf = fn(string $h) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (mb_strlen($plainOf($html)) <= $max) {
            return $html;
        }

        // paragraphs
        $paras = [];
        if (preg_match_all('#<p\b[^>]*>(.*?)</p>#is', $html, $m)) {
            foreach ($m[1] as $p) {
                $t = $plainOf($p);
                if ($t !== '') { $paras[] = $t; }
            }
        }
        if (!$paras) {
            foreach (preg_split('/\n\s*\n|\n/', strip_tags($html)) as $p) {
                $t = $plainOf($p);
                if ($t !== '') { $paras[] = $t; }
            }
        }

        $out = [];
        $used = 0;
        $done = false;
        foreach ($paras as $para) {
            $keep = [];
            foreach (preg_split('/(?<=[.!?])\s+/u', $para) as $sentence) {
                $cost = mb_strlen($sentence) + ($keep ? 1 : ($out ? 1 : 0));
                if ($used + $cost > $max) { $done = true; break; }
                $keep[] = $sentence;
                $used += $cost;
            }
            if ($keep) { $out[] = implode(' ', $keep); }
            if ($done) { break; }
        }

        if (!$out) { // the very first sentence is already too long: cut it at a word boundary
            $cut = mb_substr($paras[0] ?? $plainOf($html), 0, $max - 1);
            $cut = preg_replace('/\s+\S*$/u', '', $cut);
            $out[] = rtrim($cut, " ,;:-") . '.';
        }

        return implode('', array_map(fn($t) => '<p>' . htmlspecialchars($t, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>', $out));
    }


    /**
     * A one-line post ("We need an enrolled nurse or midwife in Kanaaba, 0701...")
     * would become a skeleton page that search engines ignore. When the description
     * we ended up with is thin, ask the model for a SHORT (about 500 characters), role-specific
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
        $appendNote = $append
            ? 'Your text is shown AFTER the original wording above, so add only what it does not already say. Do not repeat it.'
            : 'The original post has no real description, so yours is the only one the reader sees.';

        $prompt = <<<PROMPT
        You write job adverts for a Ugandan and East African job board. The original post is very short, so write
        a short job description a real hiring officer would have written for it.

        KNOWN FACTS:
        {$factList}

        {$humanRules}

        REQUIREMENTS:
        - LENGTH IS STRICT: 350 to 500 characters in total, spaces included. That is about 55 to 75 words, in one or
          two short <p> paragraphs. Never go over 500 characters. A short, believable advert is the goal; a reader
          who sees a long description for a one-line post will not trust it.
        - First sentence (this is what search and answer engines quote): who is hiring (or say the employer is not
          named), what the job is, and where. Use the job title and the place once each, in natural wording.
        - Then say, in one or two sentences, what the person does day to day and what kind of candidate suits the
          post (worded as "suits", not as a binding requirement). Be specific to this profession.
        - {$appendNote}
        - Do NOT invent the employer name, salary, benefits, deadline, number of vacancies or qualifications as
          hard requirements. Do not include phone numbers, emails or links. No bullet lists.
        - Return ONLY the HTML paragraphs. Plain <p> tags with no attributes. No markdown, no headings, no code fences.
        PROMPT;

        try {
            $text = $this->callWithFallback($model, $prompt, function ($m, $key) use ($prompt) {
                return $this->callAiApi($m, $key, $prompt);
            }, expectJson: false);

            $text = $this->cleanLetterHtml(is_string($text) ? $text : $this->extractTextFromResult($text));
            $text = $this->scrubAiTells($text);
            $text = $this->capGeneratedDescription($text);

            if (mb_strlen(trim(strip_tags($text))) >= self::GENERATED_DESC_MIN_CHARS) {
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

        $result = $this->coerceModelOutput($result);

        $result = $this->enrichThinPosting($result, $model);
        $result = $this->applySmartDefaults($result, $country);
        $result = $this->applyApplicationLinkHandling($result);
        return $this->applyGeorgiaFontToContentFields($result);
    }



    /**
    * Puts EVERY apply link (http/https) into application_procedure as
    * "To apply for this role or job click here" with "click here" hyperlinked.
    * Links the model left inside the body text are moved too, then removed from the body.
    * Emails and phone numbers are NOT touched here: they stay in their own fields.
    * Several phone numbers or emails are kept comma-separated.
    */
    protected function applyApplicationLinkHandling(array $data, ?string $sourceUrl = null): array
    {
        $bodyFields = ['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'];

        // 1. Links the model returned in the dedicated field(s)
        $links = [];
        foreach (['application_links', 'application_link'] as $f) {
            if (!empty($data[$f])) {
                $links = array_merge($links, (array) $data[$f]);
            }
        }

        // 2. Links the model left inside the body text (belt and braces)
        foreach ($bodyFields as $field) {
            if (empty($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            $links = array_merge($links, $this->findLinks($data[$field]));
        }

        // 3. Pulled from a web page that showed no separate apply link: apply on the source page
        if (!$links && $sourceUrl && filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            $links[] = $sourceUrl;
        }

        $links = $this->cleanLinkList($links);

        // 4. Remove links, emails and phone numbers from the body fields
        foreach (['job_description', 'responsibilities', 'qualifications', 'skills', 'application_procedure'] as $field) {
            if (empty($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            $clean = preg_replace('#<a\b[^>]*>(.*?)</a>#is', '$1', $data[$field]);          // keep the link text, drop the anchor
            $clean = preg_replace('#https?://[^\s<>"\']+#i', '', $clean);
            $clean = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '', $clean);
            // Ugandan-style phone numbers: 0701 220529, +256 701 220 529, 0701-220-529
            $clean = preg_replace('/(?<![\d,.])(?:\+?256[\s-]?|0)7\d{2}[\s-]?\d{3}[\s-]?\d{3}(?![\d,])/', '', $clean);
            $data[$field] = $clean;
        }

        // 5. Add the hyperlinked apply line(s) after whatever procedure the employer wrote
        if ($links) {
            $existing = (string) ($data['application_procedure'] ?? '');
            $linkHtml = $this->buildApplyLinkHtml($links);

            $data['application_procedure'] = trim(strip_tags($existing)) !== '' ? $existing . $linkHtml : $linkHtml;
            $data['is_application_required'] = true;
        }

        // 6. Several contact numbers are always comma-separated
        if (!empty($data['telephone']) && is_string($data['telephone'])) {
            $data['telephone'] = $this->normalizeTelephone($data['telephone']);
        }
        // Several emails too
        if (!empty($data['email']) && is_string($data['email'])) {
            $emails = preg_split('/\s*(?:,|;|\/|\||\band\b|\bor\b)\s*/i', $data['email']);
            $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));
            $data['email'] = implode(', ', $emails);
        }

        // Every job gets a "never pay for this job" warning in the application procedure
        $data = $this->applyPaymentWarning($data);

        unset($data['application_link'], $data['application_links']); // not form fields

        return $data;
    }

    /** Every http/https link in a piece of text or HTML (href values first, then bare links). */
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

    /** Trims trailing punctuation, keeps only valid links, removes social/share links and duplicates. */
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

    /**
    * "To apply for this role or job click here" with "click here" as the hyperlink.
    * More than one link: each gets its own line, numbered "(link 2)", "(link 3)".
    */
    protected function buildApplyLinkHtml(array $links): string
    {
        $html = '';

        foreach (array_values($links) as $i => $link) {
            $safe  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $label = $i === 0 ? 'click here' : 'click here (link ' . ($i + 1) . ')';

            $html .= '<p>To apply for this role or job <a href="' . $safe . '" target="_blank" rel="noopener noreferrer">'
                . $label . '</a></p>';
        }

        return $html;
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

        $result = $this->coerceModelOutput($result);
        $result = $this->scrubGeneratedFields($result); // all of this was written by the model
        if (!empty($result['job_description']) && is_string($result['job_description'])) {
            $result['job_description'] = $this->capGeneratedDescription($result['job_description']);
        }
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

        @set_time_limit(300);

        $deadline = microtime(true) + self::CHAIN_DEADLINE_SECONDS;

        $models = array_values(array_unique(array_merge(
            [$primaryModel],
            ['gemini', 'claude', 'openai'],
            ['grok', 'mistral'],   // only tried if enabled
            ['cohere'],            // guaranteed last resort
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

    /**
     * JSON mode makes models return real JSON arrays where the prompt asked for HTML
     * strings (skills as ["A","B"], responsibilities as ["..","..."]). Later code calls
     * trim()/strip_tags() on these, and handing them an array is a fatal TypeError, which
     * PHP reports as an HTML 500 page. The browser then fails with
     * "JSON.parse: unexpected character at line 1 column 1". Turn every field back into
     * the string shape the rest of the service expects.
     */
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
            'job_title', 'company_name', 'duty_station', 'meta_description', 'application_link', 'deadline',
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

        // application_links may arrive as an array, a string, or the old single "application_link"
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

            // Say WHY: a reply cut off by the output limit has unbalanced braces
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

        $endpoint  = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $config['max_tokens'] ?? 4096;

        $maxTokens = 8192;

        $headers = ['Content-Type' => 'application/json'];

        if (in_array($model, ['openai', 'grok', 'mistral', 'cohere'], true)) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key']         = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }

        $body = $this->buildRequestBody($model, $apiKey, $modelName, $maxTokens, $prompt, $imageBase64, $json);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        // ─────────────────────────────────────────────────────────────
        // DEBUG — log the exact request before it goes out
        // ─────────────────────────────────────────────────────────────
        if ($model === 'cohere') {
            $maskedAuth = isset($headers['Authorization'])
                ? 'Bearer ' . substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) . ' (' . strlen($apiKey) . ' chars)'
                : '(missing)';

            $bodyPreview = [
                'model'               => $body['model']       ?? null,
                'max_tokens'          => $body['max_tokens']  ?? null,
                'has_messages'        => isset($body['messages']),
                'messages_count'      => isset($body['messages']) ? count((array) $body['messages']) : 0,
                'has_prompt'          => isset($body['prompt']),
                'has_response_format' => isset($body['response_format']),
                'body_keys'           => array_keys($body),
            ];

            \Illuminate\Support\Facades\Log::info('COHERE REQUEST', [
                'endpoint'    => $endpoint,
                'auth_header' => $maskedAuth,
                'headers'     => array_merge($headers, array_intersect_key(['Authorization' => '[masked]', 'x-api-key' => '[masked]'], $headers)),
                'body'        => $bodyPreview,
                'prompt_chars'=> strlen($prompt),
                'prompt_head' => substr($prompt, 0, 200),
                'prompt_tail' => substr($prompt, -120),
            ]);
        }

        $response = Http::timeout($this->timeout)
            ->retry(3, 2000, function ($exception) {
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

        // ─────────────────────────────────────────────────────────────
        // DEBUG — log Cohere's exact response when it fails
        // ─────────────────────────────────────────────────────────────
        if ($model === 'cohere' && !$response->successful()) {
            \Illuminate\Support\Facades\Log::warning('COHERE RESPONSE (failed)', [
                'status'      => $response->status(),
                'reason'      => $response->reason(),
                'body_500'    => substr($response->body(), 0, 500),
                'headers_sel' => [
                    'content-type' => $response->header('Content-Type'),
                    'x-request-id' => $response->header('X-Request-Id'),
                    'www-authenticate' => $response->header('WWW-Authenticate'),
                ],
            ]);
        }

        if (!$response->successful()) {
            throw $this->buildApiException($model, $response);
        }

        // Also log success, so you know when it works (helps spot flakiness)
        if ($model === 'cohere') {
            \Illuminate\Support\Facades\Log::info('COHERE RESPONSE (success)', [
                'status'     => $response->status(),
                'body_chars' => strlen($response->body()),
            ]);
        }

        $json = $response->json();
        $this->assertNotTruncated($model, is_array($json) ? $json : []);

        return $this->extractResponseText($model, $json);
    }

    /**
     * A queue worker is one long-lived process. set_time_limit(300) would keep counting after
     * the job ends and kill the idle worker, so in the console the limit is removed instead
     * (the job's own $timeout still applies). In a web request the limit is raised as before.
     */
    protected function allowLongRun(int $seconds): void
    {
        @set_time_limit(app()->runningInConsole() ? 0 : $seconds);
    }

    /**
     * A reply that stopped at the output limit is incomplete, even if it parses.
     */
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

                $generationConfig = array_filter([
                    'maxOutputTokens'  => $maxTokens,
                    'responseMimeType' => $json ? 'application/json' : null,
                ]);

                if (str_contains($modelName, '2.5-flash')) {
                    $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
                } elseif (str_contains($modelName, 'gemini-3')) {
                    // Gemini 3 uses levels instead of a token budget. Extraction needs no deep reasoning.
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
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ];
                if ($json && $model === 'cohere') {
                    $body['response_format'] = ['type' => 'json_object']; // no code fences, no chatter
                }
                return $body;

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

        /**
     * Short adverts: keep the employer's text, then add a full listing. Never rejects.
     */
    protected function topUpThinListing(array $d, string $model, int $sourceWords): array
    {
        $written = $this->countWordsInFields($d, self::CONTENT_FIELDS);
        $hasSkills = trim(strip_tags((string) ($d['skills'] ?? ''))) !== '';

        if ($written >= self::ENRICH_MIN_WORDS && $hasSkills) {
            return $d;
        }

        $prompt    = $this->buildListingPrompt($d);
        $generated = null;

        try {
            $generated = $this->callWithFallback(
                $model,
                $prompt,
                fn ($m, $key) => $this->requestListing($m, $key, $prompt),
                expectJson: true
            );
        } catch (\Throwable $e) {
            Log::warning('Listing top-up: AI failed, using local fallback', ['error' => $e->getMessage()]);
        }

        $generated ??= $this->fallbackListing($d);

        return $this->mergeListing($d, $generated);
    }

        protected function fallbackListing(array $d): array
    {
        $p = $this->roleProfile($this->roleFamily((string) ($d['job_title'] ?? '')));

        return [
            'job_description'  => $this->fallbackDescription($d),
            'responsibilities' => '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $p['duties'])) . '</ul>',
            'qualifications'   => '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $p['quals'])) . '</ul>',
            'skills'           => implode(', ', $p['skills']),
        ];
    }

    protected function roleFamily(string $title): string
    {
        $t = mb_strtolower($title);

        return match (true) {
            (bool) preg_match('/clinic|nurs|midwi|doctor|medic|pharm|laborator|health|dental|hospital/', $t) => 'health',
            (bool) preg_match('/account|financ|audit|bookkeep|cashier|budget|procure/', $t) => 'finance',
            (bool) preg_match('/stores?|logist|warehouse|inventor|dispatch|supply/', $t) => 'stores',
            (bool) preg_match('/engineer|technician|mechanic|weld|plant|machin|operator|electric|driver/', $t) => 'technical',
            (bool) preg_match('/sales|market|agent|outlet|distribut|customer/', $t) => 'sales',
            default => 'general',
        };
    }

    protected function roleProfile(string $family): array
    {
        $profiles = [
            'health' => [
                'duties' => [
                    'Assess, diagnose and treat patients in line with national clinical guidelines',
                    'Keep accurate patient records and case notes',
                    'Refer patients who need specialist care and follow up on referrals',
                    'Give health education and advice to patients and their families',
                    'Manage stock of drugs and medical supplies for the unit',
                    'Report notifiable diseases and submit health data on time',
                ],
                'quals' => [
                    'A recognised diploma or degree in clinical medicine or a related health course',
                    'Valid professional registration and a current practising licence',
                    'Experience in a clinic, health centre or hospital',
                    'Knowledge of national treatment guidelines and infection prevention',
                ],
                'skills' => ['Clinical assessment', 'Diagnosis and treatment', 'Patient care', 'Record keeping',
                    'Health education', 'Infection prevention', 'Drug and supplies management', 'Emergency response',
                    'Communication with patients'],
                'para' => 'This is a clinical post, so patient safety comes before everything else. The post holder works closely with nurses, laboratory staff and facility management, and keeps to the ethics and standards of the health profession at all times.',
            ],
            'finance' => [
                'duties' => [
                    'Record financial transactions accurately and on time',
                    'Prepare reconciliations, payment vouchers and monthly reports',
                    'Check supporting documents before payments are approved',
                    'Support budget preparation and track spending against budget',
                    'Keep files ready for internal and external audit',
                    'Follow the organisation financial policies and controls',
                ],
                'quals' => [
                    'A diploma or degree in accounting, finance or a related field',
                    'Working knowledge of accounting software and Microsoft Excel',
                    'Experience in a finance or accounts role is an advantage',
                    'Strong attention to detail and honesty with money',
                ],
                'skills' => ['Bookkeeping', 'Financial reporting', 'Reconciliation', 'Microsoft Excel',
                    'Budget tracking', 'Audit preparation', 'Accounting software', 'Attention to detail', 'Confidentiality'],
                'para' => 'This post handles money and financial records, so accuracy, honesty and confidentiality are essential. The post holder reports to a finance supervisor and must keep the records clean enough to stand up to an audit at any time.',
            ],
            'stores' => [
                'duties' => [
                    'Receive, check and store goods and equipment safely',
                    'Issue items against approved requests and keep records of every movement',
                    'Carry out stock counts and report differences to the supervisor',
                    'Keep the store clean, organised and secure',
                    'Prepare stock and delivery reports on time',
                    'Coordinate transport and delivery schedules',
                ],
                'quals' => [
                    'A diploma in logistics, supply chain, business or a related field',
                    'At least two years of experience in stores, warehousing or inventory',
                    'Computer skills, including Microsoft Excel and inventory systems',
                    'Ability to lift and handle stock safely',
                ],
                'skills' => ['Inventory management', 'Stock control', 'Record keeping', 'Microsoft Excel',
                    'Logistics coordination', 'Procurement documentation', 'Time management', 'Attention to detail'],
                'para' => 'The stores function keeps the organisation running, so every item received, issued or moved must be recorded correctly. The post holder works with procurement, transport and the teams that request goods.',
            ],
            'technical' => [
                'duties' => [
                    'Carry out the day-to-day technical work of the role safely and to standard',
                    'Read and follow technical drawings, instructions and procedures',
                    'Check equipment before and after use and report faults promptly',
                    'Carry out routine maintenance and simple repairs',
                    'Keep work logs and records of tasks completed',
                    'Follow health, safety and environmental rules at all times',
                ],
                'quals' => [
                    'A certificate or diploma in a relevant technical trade',
                    'Practical experience in the trade, preferably in a similar workplace',
                    'Knowledge of workplace safety rules and the use of protective equipment',
                    'Ability to work well with a team and under supervision',
                ],
                'skills' => ['Technical troubleshooting', 'Equipment maintenance', 'Reading drawings', 'Workplace safety',
                    'Record keeping', 'Hand and power tools', 'Quality checks', 'Teamwork'],
                'para' => 'Technical work leaves little room for error, so safety and quality checks are part of every task. The post holder works with supervisors and engineers and is expected to speak up early when equipment or processes do not look right.',
            ],
            'sales' => [
                'duties' => [
                    'Meet customers and agents, answer their questions and close sales',
                    'Keep sales records and report results to the supervisor each week',
                    'Track stock levels at the outlet and request replenishment in good time',
                    'Build and maintain good relationships with customers and partners',
                    'Identify new business opportunities in the area',
                    'Represent the brand professionally at all times',
                ],
                'quals' => [
                    'Secondary education, with a certificate or diploma in sales or business an advantage',
                    'Experience in sales, marketing or customer service',
                    'Good spoken English and at least one local language',
                    'Willingness to travel within the assigned area',
                ],
                'skills' => ['Sales and negotiation', 'Customer service', 'Relationship building', 'Record keeping',
                    'Microsoft Excel', 'Stock tracking', 'Communication', 'Target achievement'],
                'para' => 'Sales results depend on trust, so the post holder must be honest, punctual and well presented. The work involves regular contact with customers and partners, and targets are reviewed monthly.',
            ],
            'general' => [
                'duties' => [
                    'Carry out the core duties of the post to the required standard',
                    'Keep accurate records and update them on time',
                    'Work with colleagues in other departments when a task needs more than one team',
                    'Report progress and problems to the supervisor promptly',
                    'Respond to queries from staff, clients or the public courteously',
                    'Prepare short weekly and monthly reports',
                ],
                'quals' => [
                    'A certificate, diploma or degree relevant to the post',
                    'Previous experience in a similar role is an advantage',
                    'Good spoken and written English',
                    'Basic computer skills, including Microsoft Word and Excel',
                ],
                'skills' => ['Communication', 'Teamwork', 'Problem solving', 'Time management', 'Report writing',
                    'Attention to detail', 'Record keeping', 'Computer literacy', 'Customer care'],
                'para' => 'The post involves regular contact with colleagues and the public, careful record keeping and meeting deadlines set by management. Training on internal systems is given where needed.',
            ],
        ];

        return $profiles[$family] ?? $profiles['general'];
    }

    /**
     * Writes roughly 420 words from the role profile, for short adverts when the AI
     * is unavailable. Paragraphs are added until the length target is reached.
     */
    protected function fallbackDescription(array $d): string
    {
        $title    = trim((string) ($d['job_title'] ?? 'this post'));
        $company  = trim((string) ($d['company_name'] ?? ''));
        $location = trim((string) ($d['duty_station'] ?? ($d['job_location_name'] ?? ''))) ?: 'Uganda';
        $type     = trim((string) ($d['employment_type'] ?? 'full-time'));
        $p        = $this->roleProfile($this->roleFamily($title));
        $who      = $company !== '' ? $company : 'The employer';

        $paras = [
            "{$who} is recruiting a {$title} to work in {$location}. This is a {$type} post, and the successful candidate is expected to start as soon as the appointment is confirmed.",
            $p['para'],
            "On a normal working day, the {$title} will carry out duties such as " . $this->joinList(array_slice($p['duties'], 0, 3)) . '.',
            'The ideal candidate is organised, reliable and comfortable working with limited supervision. Experience in a similar setting is an advantage, and the person must be willing to learn the systems and procedures the organisation uses.',
            "Working in this role gives a candidate practical experience and a clear step in a career in {$location} and beyond. Punctuality, honesty and good conduct are expected from everyone on the team.",
            'Applicants should read the requirements carefully before applying and check that they meet the qualifications listed on this page. Only applicants who meet the requirements will be considered for interview.',
        ];

        $extra = [
            'Performance is reviewed regularly against clear targets, and feedback is given so that the person can grow in the role. Staff who take initiative, keep good records and raise problems early tend to progress quickly.',
            'The post requires careful attention to detail and the ability to meet deadlines without losing quality. Records must be kept up to date, and any concern about safety, quality or cost should be raised with a supervisor at once.',
            'Teamwork is central to the work. The {title} shares information with colleagues, supports others when workloads are high, and takes part in team meetings and planning sessions.',
            'The organisation values fair treatment, respect for the public and compliance with its policies. Anyone selected receives an orientation on these standards before taking up the post.',
        ];

        $html = '';
        foreach ($paras as $para) {
            $html .= '<p>' . $para . '</p>';
        }
        foreach ($extra as $para) {
            if ($this->countWords($html) >= 420) {
                break;
            }
            $html .= '<p>' . str_replace('{title}', $title, $para) . '</p>';
        }

        return $html;
    }

    protected function joinList(array $items): string
    {
        $items = array_map(fn ($i) => mb_strtolower(rtrim($i, '.')), $items);
        $last  = array_pop($items);

        return $items ? implode(', ', $items) . ' and ' . $last : (string) $last;
    }

        /** "Full-time", "Full Time" and "full time" all compare as the same label. */
    protected function normaliseLabel(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($s)));
    }

    /** Returns the exact name from the list that matches the value, or null. */
    protected function resolveReferenceName(?string $value, array $list): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $needle = $this->normaliseLabel($value);
        foreach ($list as $item) {
            if ($this->normaliseLabel((string) $item) === $needle) {
                return $item;
            }
        }

        return null;
    }

    protected function buildListingPrompt(array $d): string
    {
        $title    = (string) ($d['job_title'] ?? '');
        $company  = trim((string) ($d['company_name'] ?? '')) ?: 'not named in the advert';
        $location = trim((string) ($d['duty_station'] ?? ($d['job_location_name'] ?? ''))) ?: 'Uganda';
        $type     = (string) ($d['employment_type'] ?? 'full-time');
        $source   = trim(strip_tags((string) ($d['job_description'] ?? ''))) ?: '(the advert gives no description)';
        $human    = $this->humanWritingRules();

        return <<<PROMPT
        You write complete job listings for a Ugandan and East African job board. The advert below is very short,
        so write the full listing a hiring officer would publish for this post.

        KNOWN FACTS (use only these; do not invent others):
        Job title: {$title}
        Employer: {$company}
        Location: {$location}
        Employment type: {$type}
        Original advert text: {$source}

        WRITE:
        - job_description: 350 to 450 words in 5 to 6 <p> paragraphs. Do NOT repeat the original advert text.
          Paragraph 1 states the job title, the employer and the location once each, in natural wording.
          Then cover what the role involves day to day, who the post serves, how it fits the work of the employer,
          what a good candidate does in the first months, and the conduct and teamwork the employer expects.
          Do not invent salary, benefits, dates, numbers or company history.
        - responsibilities: 6 to 8 items as <ul><li>...</li></ul>, specific to the job title.
        - qualifications: 4 to 6 items as <ul><li>...</li></ul>, typical for the job title. Do not add a gender
          or age requirement.
        - skills: 8 to 10 specific skills for this job title, comma-separated. Not generic soft skills only.

        {$human}

        Return ONLY a JSON object:
        {"job_description": "<p>...</p>", "responsibilities": "<ul>...</ul>", "qualifications": "<ul>...</ul>", "skills": "..."}
        No markdown, no code fences, no text before or after the JSON. No phone numbers, emails or links.
        PROMPT;
    }

    protected function requestListing(string $model, string $apiKey, string $prompt): array
    {
        $raw  = $this->callAiApi($model, $apiKey, $prompt, null, true);
        $text = is_string($raw)
            ? trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)))
            : json_encode($raw);

        $data = $this->decodeJsonLenient($text);
        if (!is_array($data)) {
            throw new \Exception('Listing reply was not valid JSON.');
        }

        $data = $this->coerceModelOutput($data);

        $words = $this->countWords((string) ($data['job_description'] ?? ''));
        if ($words < self::LISTING_MIN_WORDS) {
            throw new \Exception("Listing too short: {$words} words.");
        }

        $data['job_description'] = $this->scrubAiTells((string) $data['job_description']);

        return $data;
    }

    /**
     * Keeps the employer's own text and list items, then adds generated ones
     * until each list has a sensible length.
     */
    protected function mergeListing(array $d, array $g): array
    {
        $orig = trim(strip_tags((string) ($d['job_description'] ?? '')));
        $gen  = (string) ($g['job_description'] ?? '');
        if ($gen !== '') {
            $d['job_description'] = $orig !== '' ? trim((string) $d['job_description']) . $gen : $gen;
        }

        $d['responsibilities'] = $this->mergeListItems($d['responsibilities'] ?? null, $g['responsibilities'] ?? null, 5, 7);
        $d['qualifications']   = $this->mergeListItems($d['qualifications'] ?? null, $g['qualifications'] ?? null, 4, 6);

        if (trim(strip_tags((string) ($d['skills'] ?? ''))) === '') {
            $d['skills'] = $g['skills'] ?? '';
        }

        return $d;
    }

    protected function mergeListItems(?string $existing, ?string $generated, int $min, int $max): ?string
    {
        $items = $this->listItems((string) $existing);
        if (count($items) >= $min) {
            return $existing;
        }

        $seen = array_map(fn ($i) => mb_strtolower(strip_tags($i)), $items);
        foreach ($this->listItems((string) $generated) as $g) {
            if (count($items) >= $max) {
                break;
            }
            if (!in_array(mb_strtolower(strip_tags($g)), $seen, true)) {
                $items[] = $g;
                $seen[]  = mb_strtolower(strip_tags($g));
            }
        }

        return $items ? '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $items)) . '</ul>' : $existing;
    }

    protected function listItems(string $html): array
    {
        preg_match_all('#<li\b[^>]*>(.*?)</li>#is', $html, $m);

        return array_values(array_filter(array_map('trim', $m[1] ?? []), fn ($s) => $s !== ''));
    }

    protected function buildExtractionPrompt(string $content, string $sourceType, array $referenceData, string $mode, ?string $position = null, int $sourceWords = 0): string
    {
        $twoWeeksAhead  = Carbon::now()->addWeeks(2)->format('Y-m-d');
        $referenceBlock = $this->formatReferenceListsForPrompt($referenceData);
        $humanRules     = $this->humanWritingRules();
        $min            = self::ENRICH_TARGET_MIN_WORDS;
        $max            = self::ENRICH_TARGET_MAX_WORDS;

        $lengthRule = $sourceWords > 0
            ? "- The advert is about {$sourceWords} words. A correct copy of the chosen position is nearly as long, so do not drop, merge or shorten any bullet or paragraph."
            : '';

        if ($mode === 'enrich') {
            $modeRules = <<<RULES
            MODE: EXTRACT ONLY. The advert is short (under 400 words).
            - Copy the existing description, responsibilities, qualifications and skills as they are, in the same order.
            - Do NOT write new paragraphs or extra list items. The system adds them in a separate step.
            - If a field is missing from the advert, return null for it.
            - job_type_name: the Job Types list value that matches the advert. If the advert does not state a job type, use "Full-time".
            RULES;
        } else { 
            $modeRules = <<<RULES
            MODE: PRESERVE. The advert is long (400 words or more).
            - Copy job_description, responsibilities, qualifications, skills and application_procedure word for word, in the same order and the same structure.
            - Keep headings as <p><strong>heading</strong></p>, lists as <ul><li>, and any table as HTML table markup.
            - Do not summarise, shorten, reword or add anything to the advert text.
            - Remove only page furniture (menu text, buttons such as "Apply To Position", share links, cookie notices) and contact details, which go into the email and telephone fields.
            - job_type_name: the Job Types list value that matches the advert. If the advert does not state a job type, use "Full-time".
            RULES;
        }

        $positionRule = $position !== null && trim($position) !== ''
            ? 'Extract ONLY the position titled "' . str_replace('"', '', trim($position)) . '" (match it loosely).'
            : 'If the advert lists several positions, extract ONLY the first one.';

        $sourceInstruction = $sourceType === 'url'
            ? 'The CONTENT below was fetched from a job posting page.'
            : 'The CONTENT below is pasted job advert text.';

        return <<<PROMPT
        You are an expert job-board editor for Uganda and East Africa. Your job pages must rank in Google and be quoted by AI answer engines.

        SOURCE TYPE: {$sourceType}
        {$sourceInstruction}

        {$modeRules}

        SEVERAL POSITIONS:
        {$positionRule}
        - Use only that position's own title, duties, requirements and location.
        - Anything stated for all positions (How to Apply, email, address, documents required, deadline, employer) applies to the one you extract.
        - List the other positions in "other_positions" (for example ["Procurement Assistant (2 positions, Lango and Ankole)"]). Use [] if there is only one position.

        CONTACT DETAILS:
            - Emails go in "email". Phone numbers go in "telephone" as one comma-separated string. Never use "/", "and" or "or" to join numbers. If there is more than one email, join them with commas in "email" as well.
            - Set is_whatsapp_contact to true only if a number is described as WhatsApp. Set is_telephone_call to true if a number is described as callable, or if no qualifier is given.
            - LINKS: every web link (http or https) that a candidate can use to apply, register or submit documents (application portals, Google Forms, career pages, "Apply" buttons, job-specific pages) goes in "application_links" as an array of full URLs, in the order they appear. Do not put emails or phone numbers there. Do not include social media links or the employer's general website.
            - Never write an email address, phone number or any link inside job_description, responsibilities, qualifications, skills or application_procedure. The system writes the "click here" apply line itself. In application_procedure, keep only the employer's own instructions (documents required, deadline, subject line to use), and say "by email, using the address on this page" instead of repeating the address.

        GROUPING (keep the source's own structure):
        - job_description: overview, the role, the organisation, and the job details block (Department, Location, Reporting To and so on) as plain <p> lines.
        - responsibilities: an HTML list, <ul><li>, one item per duty. If the advert has numbered or named sub-sections, use <p><strong>heading</strong></p> followed by its own <ul>.
        - qualifications: an HTML list, <ul><li>, one item per requirement.
        - skills: a comma-separated string if the advert gives short skill names. Otherwise use the same headed-list format as responsibilities.
        - application_procedure: how to apply, with the deadline and any documents required.
        - Never put responsibilities or qualifications in plain text separated by dashes, commas or line breaks. Always use <ul><li>.

        SEO AND ANSWER-ENGINE FIELDS:
        - meta_title: 50 to 60 characters, in the form "{Job Title} at {Employer} in {Location}".
        - meta_description: 120 to 158 characters. State the role, the location and the deadline.
        - faqs: 5 to 6 questions a job seeker would type (what the job is, where it is, who can apply, the salary or that it is not stated, the deadline, how to apply). Each answer is one or two sentences using only facts from the advert.
        - Use natural language. No keyword stuffing and no repeated phrases.

        RULES FOR EVERY JOB:
        - Every number, date, title, employer and place must come from the advert. If a fact is missing, use null.
        - Never invent a salary. If none is stated, leave salary_amount null.
        - deadline must be YYYY-MM-DD or null. Convert written dates such as "20 November 2026" correctly.
        - employment_type must be one of: full-time, part-time, contract, internship, volunteer, temporary, or null.
        - Use plain HTML tags only: <p>, <h2>, <h3>, <ul>, <li>, <strong>, <table>, <tr>, <td>, <th>, <a href="...">. No style or class attributes, so the JSON cannot break.
        - Return ONLY the JSON object below, with no text or code fences around it.
        - If the content is not a real job advert (an error page, a login wall, unrelated text), return exactly {"error": true, "message": "No valid job posting was found in the provided content."}

        {$humanRules}

        REFERENCE LISTS (copy the exact text for every *_name field, or return null):
        {$referenceBlock}

        SMART DEFAULTS (only when the field is missing from the advert):
        - deadline: "{$twoWeeksAhead}"
        - employment_type: "full-time"
        - location_type: "on-site"

        OUTPUT SHAPE:
        {
          "job_title": "",
          "company_name": "exact employer name, or null (not chosen from a list)",
          "job_description": "<p>...</p>",
          "responsibilities": "<ul><li>...</li></ul>",
          "qualifications": "<ul><li>...</li></ul>",
          "skills": "",
          "application_procedure": "<p>...</p>",
          "application_links": [],
          "email": null,
          "telephone": null,
          "is_whatsapp_contact": false,
          "is_telephone_call": false,
          "deadline": "YYYY-MM-DD",
          "duty_station": null,
          "location_type": "on-site|remote|hybrid",
          "employment_type": "full-time|part-time|contract|internship|volunteer|temporary",
          "salary_amount": null,
          "payment_period": null,
          "currency": null,
          "work_hours": null,
          "meta_title": "",
          "meta_description": "",
          "keywords": "8 to 12 comma-separated search phrases",
          "faqs": [{"q": "", "a": ""}],
          "experience_level_name": null,
          "education_level_name": null,
          "industry_name": null,
          "category_name": null,
          "job_type_name": null,
          "job_location_name": null,
          "salary_range_name": null,
          "country_code": null,
          "is_urgent": false,
          "is_featured": false,
          "is_resume_required": true,
          "is_cover_letter_required": false,
          "is_academic_documents_required": false,
          "is_application_required": false,
          "other_positions": []
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
            . "dedicated email/telephone/application_links fields. If more than one contact number is "
            . "visible, list them all in telephone separated by commas (never with \"/\", \"and\", or \"or\"). "
            . "If one or more application links (http or https) are visible, put every one in application_links as an array of full URLs "
            . "(not in application_procedure and not in any other field). Emails and phone numbers are never links. "
            . "application_procedure). Do not write any warning about paying money in application_procedure, "
            . "one is added automatically. Any content you write rather than copy from the image must read as "
            . "natural, human-written, SEO-friendly copy.\n\n" . $this->humanWritingRules() . "\n\n"
            . "Never lengthen or pad text. If the image has no real description of the job, return null for "
            . "job_description: a short one is written separately by the system. "
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
            never forced. The job_description is STRICTLY 350 to 500 characters in total, spaces included
            (about 55 to 75 words) in one or two short paragraphs. Never go over 500 characters. Say what the
            person does, and what the employer wants, and stop. A long description reads as unbelievable.

            Return ONLY a valid JSON object - no explanation, no markdown, no code blocks. Do not use any HTML
            attributes (no style="...") on any tag - plain <p>/<ul>/<li> only, so nothing can break the JSON. Do not
            include any contact info or application links anywhere - this is a fresh generated posting.

            {
            "job_description": "one or two short <p> paragraphs, 350 to 500 characters in total",
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

        $verbatim = !empty($data['_verbatim']);
        unset($data['_verbatim']);

        foreach ([
            'industry_name'         => 'industries',
            'category_name'         => 'categories',
            'job_type_name'         => 'job_types',
            'job_location_name'     => 'locations',
            'experience_level_name' => 'experience_levels',
            'education_level_name'  => 'education_levels',
            'salary_range_name'     => 'salary_ranges',
        ] as $field => $refKey) {
            $data[$field] = $this->resolveReferenceName($data[$field] ?? null, $referenceData[$refKey] ?? []);
        }

        $twoWeeksAhead = Carbon::now()->addWeeks(2)->format('Y-m-d');

        $data['job_title'] = $data['job_title'] ?? '';
        $data['company_name'] = $data['company_name'] ?? '';
        $data['deadline'] = $data['deadline'] ?: $twoWeeksAhead;
        $data['location_type'] = $data['location_type'] ?: 'on-site';
        $data['employment_type'] = $data['employment_type'] ?: 'full-time';
        // Job Type: when the advert does not give one, use its employment type,
        // and if that is missing too, Full-time. Applies to short and long adverts alike.
        if (empty($data['job_type_name'])) {
            $data['job_type_name'] = $this->resolveReferenceName($data['employment_type'] ?? null, $referenceData['job_types'])
                ?? $this->resolveReferenceName('Full-time', $referenceData['job_types'])
                ?? $this->resolveReferenceName('Full Time', $referenceData['job_types'])
                ?? null;

            if ($data['job_type_name'] === null) {
                Log::warning('Job type not found in the Job Types list', ['job_types' => $referenceData['job_types']]);
            }
        }
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
        if (!$verbatim && empty(trim(strip_tags((string) ($data['job_description'] ?? '')))) && !empty($data['job_title'])) {
            $data['job_description'] = $this->generateFallbackDescription($data['job_title'], $data['company_name'] ?: null, $data['duty_station']);
        }
        if (!$verbatim && empty(trim(strip_tags((string) ($data['responsibilities'] ?? '')))) && !empty($data['job_title'])) {
            $data['responsibilities'] = $this->generateFallbackResponsibilities($data['job_title'], $data['company_name'] ?: null);
        }
        if (!$verbatim && empty(trim(strip_tags((string) ($data['qualifications'] ?? '')))) && !empty($data['job_title'])) {
            $data['qualifications'] = $this->generateFallbackQualifications($data['job_title']);
        }
        if (!$verbatim && empty(trim((string) ($data['skills'] ?? ''))) && !empty($data['job_title'])) {
            $data['skills'] = $this->generateFallbackSkills($data['job_title']);
        }
        if (empty(trim((string) ($data['meta_description'] ?? ''))) && !empty($data['job_title'])) {
            $data['meta_description'] = $this->generateFallbackMetaDescription($data['job_title'], $data['company_name'] ?: null, $data['duty_station']);
        }
        if (empty(trim((string) ($data['keywords'] ?? ''))) && !empty($data['job_title'])) {
            $data['keywords'] = $this->generateFallbackKeywords($data['job_title']);
        }
        $data['application_procedure'] = $data['application_procedure'] ?? '';
        $data['other_positions'] = is_array($data['other_positions'] ?? null) ? $data['other_positions'] : [];

        // SEO safety net: even if job_description came back non-empty (verbatim from the
        // source, or from the block above), it might still be too thin (e.g. a source
        // page that only had a one-line title with no real description) to be useful for
        // search engines. Pad it out with additional role-appropriate detail rather than
        // discarding whatever real content was there.
        if (!$verbatim && !empty($data['job_title'])) {
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
        // Only a near-empty description is topped up (the old 120-word rule doubled the length of
        // descriptions that were already fine). The added text is itself kept under the 500-character cap.
        if (mb_strlen(trim(preg_replace('/\s+/u', ' ', strip_tags($description)))) >= self::GENERATED_DESC_MIN_CHARS) {
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
            return $this->capGeneratedDescription($body . $close);
        }

        return $this->capGeneratedDescription($this->scrubAiTells($pick($openers, 'o') . $body . $close));
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

        $models = $this->getOrderedCvModels();

        foreach ($models as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt, null, true);

                if (!$raw) {
                    continue;
                }

                $data = $this->parseJsonResponse($raw);

                if ($data === null || empty($data)) {
                    Log::warning("⚠️ {$model} returned unparseable response - trying next model");
                    continue;
                }

                // ────────────────────────────────────────────────────────
                // NEW: honour the AI's validity verdict
                // ────────────────────────────────────────────────────────
                if (array_key_exists('_valid', $data)) {
                    if ($data['_valid'] === false) {
                        // The AI says this isn't a CV → abort the whole loop and tell the user
                        $reason = $data['_reason']
                            ?? 'This does not look like a CV. Please upload your CV as a PDF or Word document.';

                        Log::info("CV validation rejected by {$model}", [
                            'reason' => $reason,
                        ]);

                        // Throw a dedicated exception the controller can catch
                        throw new \App\Exceptions\NotACvException($reason);
                    }

                    // Strip the meta fields before returning
                    unset($data['_valid'], $data['_reason']);
                }

                if ($this->hasValidCvData($data)) {
                    return $data;
                }

                Log::warning("⚠️ {$model} returned empty data - trying next model");

            } catch (\App\Exceptions\NotACvException $e) {
                // Non-CV verdict is authoritative — do NOT try other models.
                // Trying another model could produce a different answer, which is
                // confusing. One model's verdict is enough to reject the upload.
                throw $e;

            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("⚠️ CV extraction failed with {$model}: " . $e->getMessage());
            }
        }

        Log::error('All AI models failed to extract CV data', [
            'last_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

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
        You are a strict data-extraction engine for a job board. The user has uploaded a file that
        should be their CV (resume). Your first job is to decide whether the file really IS a CV.

        ═══════════════════════════════════════════════════════════════
        STEP 1: IS THIS A CV?
        ═══════════════════════════════════════════════════════════════
        Answer "yes" ONLY if the text contains at least THREE of these signals:
            - A person's name (full name, not a company name)
            - A contact detail (phone number, email, address)
            - Work experience with employer names and/or job titles
            - Education (school, degree, diploma, certificate)
            - Skills, training, or certifications
            - A professional summary or career objective

        Answer "no" if the text is:
            - A job advert / job description (talks about "we are hiring", "the candidate will...")
            - A cover letter (only one job, starts with "Dear Sir/Madam", no employment history)
            - A certificate, transcript, or ID document (no experience, only one institution)
            - A receipt, invoice, chat screenshot, or random document
            - Empty, scanned-as-image with no text, or a password-protected file
            - Too short to be a CV (under 100 words of real content)
            - A company profile, brochure, or marketing material
            - A CV that belongs to MULTIPLE people (multiple names as candidates)
            - Totally unrelated content

        If it is NOT a CV, return ONLY this JSON and stop:
            {"_valid": false, "_reason": "<friendly 1-sentence explanation for the user>"}

        Use these friendly reason templates:
            - "This does not look like a CV. Please upload your CV as a PDF or Word document."
            - "This looks like a job advert, not a CV. Please upload your own CV."
            - "This looks like a cover letter, not a full CV. Please upload your complete CV."
            - "This looks like a certificate or transcript. Please upload your full CV."
            - "This file is empty or unreadable. Please upload a PDF or Word CV with real text."
            - "This content is too short to be a CV. Please upload your full CV."
            - "This does not appear to belong to one person. Please upload your own CV."

        ═══════════════════════════════════════════════════════════════
        STEP 2: EXTRACT THE CV DATA (only if _valid = true)
        ═══════════════════════════════════════════════════════════════
        Return ONLY a single valid JSON object — no markdown, no commentary, no code fences.
        Use null or an empty array for any field you cannot find. Never invent data.

        {
        "_valid": true,
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

        For education, include a "grade" field (e.g., "A", "B+", "First Class", "Distinction", "GPA: 3.8/4.0") if mentioned, otherwise null.

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
            case 'cohere':
            case 'mistral':
                $body = [
                    'model'      => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ];
                if ($json) {
                    $body['temperature'] = 0;   // copy faithfully, do not paraphrase
                }
                if ($json && $model === 'cohere') {
                    $body['response_format'] = ['type' => 'json_object']; // no code fences, no chatter
                }
                return $body;
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
        $jobTitle       = $jobContext['job_title']    ?? 'the advertised post';
        $companyName    = $jobContext['company_name'] ?? 'the organisation';
        $jobDescription = strip_tags($jobContext['job_description'] ?? '');
        $jobDescription = $jobDescription !== '' ? $jobDescription : '(no detailed description provided)';

        // Optional: the caller can pass 'company_address', 'duty_station' or 'location' so the
        // recipient block can carry the company's town/address. Without any of them, the line is omitted.
        $placeRaw = trim((string) ($jobContext['company_address'] ?? $jobContext['duty_station'] ?? $jobContext['location'] ?? ''));
        $jobPlace = $placeRaw !== '' ? "Company address / town: {$placeRaw}\n            " : '';

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
            {$jobPlace}Job Description / Context:
            {$jobDescription}

            TODAY'S DATE (use it in the letter): {$today}

            CANDIDATE CV (raw text extracted from their CV):
            ---
            {$cvText}
            ---

            STRUCTURE — follow this EXACTLY, in this order:

            ── 1. CANDIDATE HEADER (top of the letter, right side or left,
                no label needed) ──
            Line 1: the candidate's full name (from the CV, in bold)
            Line 2: their town/city and country (e.g. "Kampala, Uganda") — from the CV if present, else omit this line
            Line 3: their phone number and email, separated by " | " (from the CV)
            Do NOT invent a postal address or P.O. Box if the CV doesn't have one.

            ── 2. DATE (own line, right-aligned) ──
            "{$today}"

            ── 3. RECIPIENT BLOCK (left-aligned, after the date) ──
            Line 1: "The Human Resource Manager" (default title for all formal applications)
            Line 2: The company name exactly as provided: "{$companyName}"
            Line 3: the "Company address / town" given above, exactly as written — omit this line if none is given
            Line 4: "Uganda" (only if the previous line is present and doesn't already include "Uganda")
            Do NOT invent a P.O. Box, postal code, street address, or HR manager's personal name.
            If the job context names a specific person (e.g. "The Managing Director"), use that instead of "The Human Resource Manager".

            ── 4. SUBJECT LINE (own paragraph, bold, in capitals) ──
            {$subjectLine}

            ── 5. SALUTATION ──
            "Dear Sir/Madam," (unless the job context names a specific person — then use "Dear Mr./Ms. [Surname],")

            ── 6. BODY — four paragraphs, in this order ──

            Paragraph 1 (Opening, 2-3 sentences):
            - {$openingInstruction}
            - Name the exact job title and where it was advertised if the CV or job context mentions it.
            - State that your skills match what {$companyName} is looking for.

            Paragraph 2 (Work experience, 3-5 sentences):
            - Name the most recent relevant employer(s) from the CV.
            - Describe 2-3 concrete duties that match this job.
            - Include numbers (years of experience, clients handled per day,
                records managed, transactions processed) whenever the CV provides them.
            - Do NOT invent numbers.

            Paragraph 3 (Qualifications and skills, 2-4 sentences):
            - Name the highest qualification from the CV (degree, diploma, certificate).
            - Mention 2-3 practical skills that matter for this job (software, languages, tools).
            - State any relevant professional certifications.
            - Add one sentence linking those skills directly to what the job advert asks for.

            Paragraph 4 (Personal qualities and availability, 2-3 sentences):
            - Name 3 personal qualities (e.g. "polite, organised and reliable").
            - End with: "I am available to start immediately and would be glad to discuss how I can support {$companyName} at an interview."
            - Add "Thank you for your time and consideration."

            ── 7. SIGN-OFF ──
            "Yours faithfully," (always, not "Yours sincerely")
            Then two blank lines
            Then the candidate's full name as it appears on the CV, in bold.

            HARD RULES:
            - Length: 300 to 400 words total (excluding the address blocks).
            - Tone: formal, respectful, direct. Plain English — no "I am
            thrilled", no "passionate", no "dynamic", no em-dashes.
            - Do NOT include placeholder brackets like [Your Name],
            [Company Name], [Date], [Job Title], [P.O. Box]. Fill everything
            from the CV and the job context provided, or OMIT the line
            entirely if the data isn't available.
            - Do NOT invent employers, dates, qualifications, salary, P.O. Boxes,
            street addresses, or achievements. Only use what is in the CV.
            - If the CV is thin, write a strong but honest letter that leans
            on transferable skills — never fabricate experience.
            - Do NOT use markdown bold, italics, or headings inside the body.
            - Return clean HTML using only <p>, <strong> and <br> tags. No
            attributes on any tag. No <html>, <head>, or <body> wrapper.
            - Do NOT wrap the response in markdown code fences. No commentary
            before or after the letter.

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

        // Strip attributes from every tag
        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr', 'img'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        // Drop empty <p>
        $clean = preg_replace('#<p>\s*</p>#i', '', $clean);

        // If the model returned plain text, wrap blocks in <p>
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