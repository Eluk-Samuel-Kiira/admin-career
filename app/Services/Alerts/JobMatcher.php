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

        // ── 3.5-DAY WINDOW (Efficiency Buffer) ──
        $windowStart = now()->subDays(3)->subHours(12)->startOfDay();

        $query = JobPost::with(['company:id,name', 'jobLocation'])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $windowStart)
            ->orderByDesc('published_at');

        // ── Country ──
        $countryCode = $this->resolveCountryCode($seeker);
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        // ── Category Filter ──
        $hasCategoryFilter = !$wantsAll && !empty($cats);
        if ($hasCategoryFilter) {
            $query->whereIn('job_category_id', $cats);
        }

        // Fetch jobs (Max 500 for memory efficiency)
        $jobs = $query->limit(500)->get();

        // ─────────────────────────────────────────────────────────────
        // EFFICIENCY LOGIC:
        // If the user picked a Category, we ALREADY know these are the jobs they want.
        // Running the AI Skill Matcher on them is a waste of resources.
        // We ONLY run the skill matcher if the user did NOT pick a category.
        // ─────────────────────────────────────────────────────────────
        if ($useSkill && !$hasCategoryFilter && $jobs->isNotEmpty()) {
            
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
                // Seeker has no skills on file, fallback to unfiltered jobs
                $jobs = $scored->pluck('job')->values();
            }
        }

        return $jobs->take($limit)->values();
    }

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

    protected function toCode(?string $value): ?string
    {
        if ($value === null) return null;

        $value = trim($value);
        if ($value === '') return null;

        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        $key = strtolower($value);
        if (array_key_exists($key, self::$countryCache)) {
            return self::$countryCache[$key];
        }

        $code = Country::whereRaw('LOWER(name) = ?', [$key])
            ->orWhereRaw('LOWER(code) = ?', [$key])
            ->value('code');

        return self::$countryCache[$key] = $code ? strtoupper($code) : null;
    }
}