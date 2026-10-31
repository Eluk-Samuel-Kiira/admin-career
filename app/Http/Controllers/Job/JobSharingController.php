<?php

namespace App\Http\Controllers\Job;

use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Job\JobPost;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class JobSharingController extends Controller
{
    private const PER_BATCH        = 10;
    private const MAX_SKILLS       = 4;   // show at most 4 skills per job
    private const SKILLS_MAX_CHARS = 60;  // and keep the skills line short
    private const BRAND            = 'Stardena Careers';
    private const CONTACT_PHONE    = '+256 754 428612';
    private const CONTACT_EMAIL    = 'stardenacareers@gmail.com';
    private const DIVIDER          = '━━━━━━━━━━━━━━━';
    private const JOB_DIVIDER      = '=========================';

    // Rotated by day and part so repeated posts don't read the same
    private const CTA_HOOKS = [
        '"Recruiters spend seconds on a CV. Let\'s make yours count."',
        '"The best job on this list could be yours, with the right CV."',
        '"Great jobs get hundreds of applicants. A sharp CV gets the interview."',
        '"Don\'t just apply. Apply to stand out."',
        '"Your skills are ready. Is your CV?"',
    ];

    public function index()
    {
        if (!auth()->user()->can('view jobs')) {
            abort(403, 'You do not have permission to view job sharing.');
        }

        return view('job.job-sharing.index');
    }

    /**
     * GET /admin/job-sharing/data?range=today|yesterday|last3|week|all&country=UG
     *
     * Jobs are grouped by country first, then split into batches of 10,
     * so every WhatsApp message is about one country only.
     */
    public function data(Request $request)
    {
        if (!auth()->user()->can('view jobs')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        [$from, $to] = $this->resolveRange((string) $request->get('range', 'today'));
        $batches = $this->buildBatches($request);

        $data = $batches->map(function (array $b) {
            $info = $this->countryInfo($b['country_code']);

            return [
                'part'          => $b['part'],
                'country_code'  => $b['country_code'],
                'country_label' => trim($info['flag'] . ' ' . ($info['name'] ?: 'Other')),
                'country_part'  => $b['country_part'],
                'country_parts' => $b['country_parts'],
                'count'         => $b['jobs']->count(),
                'jobs'          => $b['jobs']->map(fn($j) => $this->formatJobRow($j))->values(),
                'share_text'    => $this->buildBatchShareText($b),
            ];
        })->values();

        return response()->json([
            'success'       => true,
            'from'          => $from->toIso8601String(),
            'to'            => $to->toIso8601String(),
            'total_jobs'    => $batches->sum(fn($b) => $b['jobs']->count()),
            'total_batches' => $batches->count(),
            'per_batch'     => self::PER_BATCH,
            'data'          => $data,
        ]);
    }

    /**
     * GET /admin/job-sharing/batch/{n}
     * Returns the plain-text message for a single part.
     */
    public function batch(Request $request, int $n)
    {
        if (!auth()->user()->can('view jobs')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $batch = $this->buildBatches($request)->firstWhere('part', $n);

        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found'], 404);
        }

        return response()->json([
            'success'    => true,
            'part'       => $n,
            'share_text' => $this->buildBatchShareText($batch),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Batching
    // ─────────────────────────────────────────────────────────────

    /**
     * Query the jobs, group by country, chunk each country into batches of 10.
     * `part` is a global, stable index used by the copy button.
     */
    private function buildBatches(Request $request): Collection
    {
        [$from, $to] = $this->resolveRange((string) $request->get('range', 'today'));
        $country = strtoupper((string) $request->get('country', ''));

        // load the location relation only if the model defines it
        $with = ['company:id,name'];
        if (method_exists(JobPost::class, 'jobLocation')) {
            $with[] = 'jobLocation';
        }

        $query = JobPost::with($with)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$from, $to]);

        if ($country !== '') {
            $query->where('country_code', $country);
        }

        $jobs = $query->orderByDesc('published_at')->get();

        $batches = collect();
        $part    = 0;

        foreach ($jobs->groupBy(fn($j) => $j->country_code ?: 'XX')->sortKeys() as $code => $group) {
            $chunks = $group->chunk(self::PER_BATCH)->values();

            foreach ($chunks as $i => $chunk) {
                $part++;
                $batches->push([
                    'part'          => $part,
                    'country_code'  => (string) $code,
                    'country_part'  => $i + 1,
                    'country_parts' => $chunks->count(),
                    'jobs'          => $chunk->values(),
                ]);
            }
        }

        return $batches;
    }

    private function resolveRange(string $range): array
    {
        $now = now();

        return match ($range) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'last3' => [
                $now->copy()->subDays(3)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'week' => [
                $now->copy()->subDays(7)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'all' => [
                $now->copy()->subYear()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            default => [ // today
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    // ─────────────────────────────────────────────────────────────
    // Table rows (admin screen)
    // ─────────────────────────────────────────────────────────────

    private function formatJobRow(JobPost $job): array
    {
        return [
            'id'        => $job->id,
            'title'     => $job->job_title,
            'company'   => $job->company?->name ?? 'Company',
            'url'       => $job->generateUrl(), // same country-correct URL the indexer uses
            'location'  => $this->locationText($job),
            'skills'    => $this->formatSkills($job->skills),
            'deadline'  => $job->deadline?->format('D, j M Y'),
            'published' => $job->published_at?->format('Y-m-d H:i'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // WhatsApp message
    // ─────────────────────────────────────────────────────────────

    /**
     * Layout:
     *   header (country + brand + date)
     *   10 jobs (title, company/location, top skills, deadline, link)
     *   CV / cover letter call at the very end
     */
    private function buildBatchShareText(array $batch): string
    {
        $jobs  = $batch['jobs'];
        $info  = $this->countryInfo($batch['country_code']);
        $name  = $info['name'];
        $flag  = $info['flag'];
        $count = $jobs->count();

        $heading = $name !== '' ? mb_strtoupper($name) . ' JOB ALERT' : 'JOB ALERT';
        $partTxt = $batch['country_parts'] > 1
            ? " • Part {$batch['country_part']} of {$batch['country_parts']}"
            : '';

        $lines = [
            trim("{$flag} *{$heading}* {$flag}"),
            '_' . self::BRAND . ' • ' . now()->format('D, j M Y') . '_',
            "📌 *{$count} new " . ($count === 1 ? 'opening' : 'openings') . "{$partTxt}*",
            '_Tap a link to read the full details and apply._',
            '',
            self::DIVIDER,
            '',
        ];

        $firstUrl = null;

        foreach ($jobs as $i => $job) {
            $title  = $this->cleanTitle($job->job_title);
            $where  = $this->locationText($job);
            $skills = $this->formatSkills($job->skills);
            $url    = $job->generateUrl();
            $firstUrl ??= $url;

            // title, location, skills, date, link
            $lines[] = "🔎 *{$title}*";
            if ($where)         $lines[] = "📍 {$where}";
            if ($skills !== '') $lines[] = "💡 {$skills}";
            $lines[] = '🗓️ ' . $this->deadlineText($job);
            $lines[] = "🔗 {$url}";
            $lines[] = self::JOB_DIVIDER;
            $lines[] = '';
        }

        $hook = self::CTA_HOOKS[(now()->dayOfYear + $batch['part']) % count(self::CTA_HOOKS)];

        $lines[] = '📄 *CV & COVER LETTER BOOST*';
        $lines[] = "✨ _{$hook}_";
        $lines[] = '';
        $lines[] = '🔹 *Elevate* your CV so it works for any job you go after';
        $lines[] = '🎯 *Tailor* your CV and cover letter to any job listed above';
        $lines[] = '';
        $lines[] = '💬 Message us today and get noticed:';
        $lines[] = '📞 Call / WhatsApp: ' . self::CONTACT_PHONE;
        $lines[] = '✉️ ' . self::CONTACT_EMAIL;

        if ($firstUrl && ($p = parse_url($firstUrl)) && !empty($p['host'])) {
            $lines[] = '';
            $lines[] = '🌐 More jobs daily: ' . ($p['scheme'] ?? 'https') . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . '/jobs';
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * Location line: the job's own duty_station first; if empty, fall back to
     * the linked JobLocation (district, then city, then region).
     */
    private function locationText(JobPost $job): ?string
    {
        $candidates = [$job->duty_station];

        $loc = $job->relationLoaded('jobLocation') ? $job->jobLocation : null;
        if ($loc) {
            $candidates[] = $loc->district;
            $candidates[] = $loc->city;
            $candidates[] = $loc->region;
        }

        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return Str::limit($this->cleanTitle($value), 40, '');
            }
        }

        return null;
    }

    private function deadlineText(JobPost $job): string
    {
        if (!$job->deadline) {
            return 'Open until filled';
        }

        return $job->deadline->format('jS M Y'); // 7th Nov 2026
    }

    /**
     * Skills are stored as messy rich-text HTML ("A, B, C"). Strip the markup
     * and return the first few short skills as "A • B • C".
     */
    private function formatSkills($raw): string
    {
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        // regex first: the stored HTML has broken attributes that confuse strip_tags
        $text = preg_replace('/<[^>]*>/u', ' ', (string) $raw);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        $out = [];
        $len = 0;

        foreach (preg_split('/[,;\n\r•|]+/u', $text) as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part), " \t.-–—");

            if ($part === '' || mb_strlen($part) > 28) {
                continue;
            }

            $next = $len + mb_strlen($part) + ($out ? 3 : 0);
            // too long to fit: skip it and try the next (shorter) skill
            if ($next > self::SKILLS_MAX_CHARS) {
                continue;
            }

            $out[] = $part;
            $len   = $next;

            if (count($out) >= self::MAX_SKILLS) {
                break;
            }
        }

        return implode(' • ', $out);
    }

    /** Keep WhatsApp bold (*) from breaking, collapse whitespace, cap length. */
    private function cleanTitle(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', str_replace('*', '', $text)));

        return Str::limit($text, 90, '…');
    }

    /** code => ['name' => ..., 'flag' => ...] */
    private function countryInfo(string $code): array
    {
        static $map = null;

        if ($map === null) {
            $map = [];
            foreach (CountryHelper::getCountriesWithFlags() as $c) {
                $map[strtoupper($c['code'])] = [
                    'name' => $c['name'] ?? '',
                    'flag' => $c['flag'] ?? '',
                ];
            }
        }

        $code = strtoupper($code);

        return $map[$code] ?? ['name' => $code === 'XX' ? '' : $code, 'flag' => ''];
    }
}