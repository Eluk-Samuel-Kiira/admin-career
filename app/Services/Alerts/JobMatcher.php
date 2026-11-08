<?php

namespace App\Services\Alerts;

use App\Models\Job\Country;
use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use App\Services\Skills\SkillMatcher;
use Illuminate\Support\Collection;

class JobMatcher
{
    public const MAX_JOBS = 10;

    /** Request-scoped cache: name/code → ISO code */
    protected static array $countryCache = [];

    public function __construct(protected SkillMatcher $skillMatcher) {}

    public function match(SeekerProfile $seeker, array $prefs, int $limit = self::MAX_JOBS): Collection
    {
        $wantsAll  = !empty($prefs['all_job_alerts']);
        $cats      = array_values(array_filter((array) ($prefs['alert_by_category'] ?? [])));
        $useSkill  = !empty($prefs['alert_by_skill_match']) || !empty($prefs['alert_by_cv_skill']);
        $threshold = (int) ($prefs['skill_match_threshold'] ?? 50);

        $query = JobPost::with(['company:id,name', 'jobLocation'])
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('deadline', '>=', now())
            ->orderByDesc('published_at');

        // ── Country (resolve name → ISO code via Country model)
        $countryCode = $this->resolveCountryCode($seeker);
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        // ── Category filter (job_category_id IN …) unless "all jobs" mode
        if (!$wantsAll && !empty($cats)) {
            $query->whereIn('job_category_id', $cats);
        }

        $jobs = $query->limit(500)->get();

        // ── Skill threshold (only when enabled)
        if ($useSkill && $jobs->isNotEmpty()) {
            $scored = $jobs->map(fn ($j) => [
                'job'   => $j,
                'score' => $this->skillMatcher->score($seeker, $j),
            ]);

            $anyScore = (int) ($scored->max('score') ?? 0);

            if ($anyScore > 0) {
                $jobs = $scored
                    ->filter(fn ($r) => $r['score'] >= $threshold)
                    ->sortByDesc('score')
                    ->pluck('job')
                    ->values();
            } else {
                // Seeker has no usable skills OR job fields are empty.
                // Don't nuke the whole list — fall back to category/country filtered jobs.
                \Log::info('JobMatcher: skill score was 0 for every candidate — skipping threshold', [
                    'seeker_id'  => $seeker->id,
                    'candidates' => $jobs->count(),
                ]);
                $jobs = $scored->pluck('job')->values();
            }
        }

        return $jobs->take($limit)->values();
    }

    /**
     * Try preferred_country_code → user.country_code → seeker.country,
     * return a 2-char ISO code. Handles both "UG" and "Uganda".
     */
    protected function resolveCountryCode(SeekerProfile $seeker): ?string
    {
        $candidates = [
            $seeker->preferred_country_code,
            $seeker->user?->country_code,
            $seeker->country,
        ];

        foreach ($candidates as $value) {
            $code = $this->toCode($value);
            if ($code) return $code;
        }

        return null;
    }

    /**
     * "UG" → "UG"; "Uganda" → "UG"; "uganda" → "UG"; "" → null.
     */
    protected function toCode(?string $value): ?string
    {
        if ($value === null) return null;

        $value = trim($value);
        if ($value === '') return null;

        // Already a 2-letter code
        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        $key = strtolower($value);
        if (array_key_exists($key, self::$countryCache)) {
            return self::$countryCache[$key];
        }

        // Look up via Country model (name is unique enough; also try code)
        $code = Country::whereRaw('LOWER(name) = ?', [$key])
            ->orWhereRaw('LOWER(code) = ?', [$key])
            ->value('code');

        return self::$countryCache[$key] = $code ? strtoupper($code) : null;
    }
}