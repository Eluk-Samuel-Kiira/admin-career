<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Models\Job\JobPost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class JobSharingController extends Controller
{
    private const PER_BATCH = 10;

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
     * Returns jobs grouped into batches of 10. Each batch has the ready-to-share
     * WhatsApp text pre-rendered so the copy button is a one-liner.
     */
    public function data(Request $request)
    {
        if (!auth()->user()->can('view jobs')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        [$from, $to] = $this->resolveRange($request->get('range', 'today'));

        $country = strtoupper((string) $request->get('country', ''));

        $query = JobPost::with(['company:id,name'])
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$from, $to]);

        if ($country !== '') {
            $query->where('country_code', $country);
        }

        $jobs = $query->orderByDesc('published_at')->get();

        // Group into batches of 10
        $batches = $jobs->chunk(self::PER_BATCH)->values()->map(function ($chunk, $index) use ($from, $to) {
            $part = $index + 1;

            return [
                'part'        => $part,
                'count'       => $chunk->count(),
                'jobs'        => $chunk->map(fn($j) => $this->formatJobRow($j))->values(),
                'share_text'  => $this->buildBatchShareText($chunk, $part, $from, $to),
            ];
        });

        return response()->json([
            'success'      => true,
            'from'         => $from->toIso8601String(),
            'to'           => $to->toIso8601String(),
            'total_jobs'   => $jobs->count(),
            'total_batches'=> $batches->count(),
            'per_batch'    => self::PER_BATCH,
            'data'         => $batches,
        ]);
    }

    /**
     * GET /admin/job-sharing/batch/{n}
     * Returns the plain-text batch for a single part.
     */
    public function batch(Request $request, int $n)
    {
        if (!auth()->user()->can('view jobs')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        [$from, $to] = $this->resolveRange($request->get('range', 'today'));
        $country = strtoupper((string) $request->get('country', ''));

        $query = JobPost::with(['company:id,name'])
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$from, $to]);

        if ($country !== '') {
            $query->where('country_code', $country);
        }

        $jobs = $query->orderByDesc('published_at')->get();

        $chunks = $jobs->chunk(self::PER_BATCH)->values();

        if (!isset($chunks[$n - 1])) {
            return response()->json(['success' => false, 'message' => 'Batch not found'], 404);
        }

        return response()->json([
            'success'    => true,
            'part'       => $n,
            'share_text' => $this->buildBatchShareText($chunks[$n - 1], $n, $from, $to),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Returns [Carbon $from, Carbon $to] for the requested range.
     * 'to' is always now (end of the current day for today).
     */
    private function resolveRange(string $range): array
    {
        $now = now();

        return match ($range) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'last3'     => [
                $now->copy()->subDays(3)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'week'      => [
                $now->copy()->subDays(7)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'all'       => [
                $now->copy()->subYear()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            default     => [ // today
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Shape the job for the table and for building share text.
     */
    private function formatJobRow(JobPost $job): array
    {
        return [
            'id'        => $job->id,
            'title'     => $job->job_title,
            'company'   => $job->company?->name ?? 'Company',
            'url'       => $this->buildJobUrl($job),
            'deadline'  => $job->deadline?->format('l, F j Y'),
            'published' => $job->published_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Build the public URL exactly the way the site serves it.
     * Matches the pattern used by the sitemap: /job/{slug}
     */
    private function buildJobUrl(JobPost $job): string
    {
        $base = rtrim(config('app.url'), '/');

        // If the job knows its own country domain, prefer that (multi-country setup).
        if ($job->country_code) {
            $countryDomain = Cache::remember(
                'country_domain_' . $job->country_code,
                now()->addHour(),
                fn() => \App\Models\Job\Country::where('code', $job->country_code)->value('domain')
            );
            if ($countryDomain) {
                $scheme = str_starts_with($countryDomain, 'http') ? '' : 'https://';
                $base = rtrim($scheme . $countryDomain, '/');
            }
        }

        return $base . '/job/' . $job->slug;
    }

    
    /**
     * Build the full batch text in the WhatsApp format you supplied.
     *
     * The intro and outro blocks bracket the batch of 10 jobs so the reader
     * sees the call to action on both ends of the message.
     */
    private function buildBatchShareText($jobs, int $part, Carbon $from, Carbon $to): string
    {
        $header = sprintf(
            "*JOBS SHARED ON %s PART %d*",
            now()->format('Y-m-d H:i:s'),
            $part
        );

        $intro = $this->buildPromoBlock();
        $outro = $this->buildPromoBlock();

        $lines = [
            $header,
            '',
            $intro,
            '',
            '=========================================',
            '',
        ];

        foreach ($jobs as $job) {
            $title    = $this->cleanTitle($job->job_title, $job->company?->name);
            $url      = $this->buildJobUrl($job);
            $deadline = $job->deadline?->format('l, F j Y') ?? 'Open';

            $lines[] = "*{$title}*";
            $lines[] = $url;
            $lines[] = 'Deadline of this Job: "' . $deadline . '"';
            $lines[] = '';
        }

        $lines[] = '=========================================';
        $lines[] = '';
        $lines[] = $outro;

        return rtrim(implode("\n", $lines));
    }

    /**
     * The promotional block shown at the top and bottom of every batch.
     * Kept short, punchy, and WhatsApp-friendly (no markdown tables).
     */
    private function buildPromoBlock(): string
    {
        return <<<TEXT
            *Elevate Your Career with Professional Branding Solutions!*

            • CV / Resume Writing – UGX 15000
            Stand out with an ATS-compliant, professionally structured resume tailored to highlight your core strengths and achievements.

            • Targeted Cover Letter – UGX 5000
            Get a compelling cover letter customized to communicate your value proposition to prospective employers.

            *Contact Us Today:*
            Call / WhatsApp: 0754428612
            Email: stardenacareers@gmail.com
            TEXT;
    }

    /**
     * Build a display title like the sample:
     *   "Request For Comments ... tender at Uganda Communications Commission"
     * If the DB title already contains " at ", keep it as-is.
     * Otherwise append " at {company}".
     */
    private function cleanTitle(string $title, ?string $company): string
    {
        $title = trim($title);

        if ($company && !str_contains(strtolower($title), ' at ')) {
            $title .= ' at ' . trim($company);
        }

        return $title;
    }
}