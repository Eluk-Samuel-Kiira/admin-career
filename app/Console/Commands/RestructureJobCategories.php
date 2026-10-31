<?php

namespace App\Console\Commands;

use App\Models\Job\JobCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RestructureJobCategories extends Command
{
    protected $signature = 'categories:restructure
                            {country? : Country code (UG, KE, ...). Omit for all}
                            {--dry-run : Run everything, then roll back}
                            {--keep-merged : Deactivate merged duplicates instead of deleting them}';

    protected $description = 'Rename categories to a standard taxonomy, keeping existing IDs';

    // CHECK THESE against your schema
    private const JOBS_TABLE = 'job_posts';
    private const JOBS_FK    = 'job_category_id';

    private const COUNTRIES = [
        'AU' => 'Australia', 'UG' => 'Uganda', 'KE' => 'Kenya', 'RW' => 'Rwanda',
        'TZ' => 'Tanzania', 'MW' => 'Malawi', 'ZM' => 'Zambia', 'SG' => 'Singapore',
        'ZA' => 'South Africa',
    ];

    // key => display name. Order here = sort_order.
    private const TAXONOMY = [
        'accounting'     => 'Accounting & Auditing',
        'banking'        => 'Banking, Microfinance & Financial Services',
        'insurance'      => 'Insurance',
        'admin'          => 'Administration & Office Support',
        'marketing'      => 'Marketing, Advertising & Communications',
        'sales'          => 'Sales & Business Development',
        'customer'       => 'Customer Service & Support',
        'it'             => 'Information Technology & Support',
        'software'       => 'Software & Web Development',
        'data'           => 'Data, Analytics & Monitoring and Evaluation',
        'telecom'        => 'Telecommunications',
        'engineering'    => 'Engineering (General)',
        'civil'          => 'Construction & Civil Engineering',
        'electrical'     => 'Electrical & Electronics Engineering',
        'mechanical'     => 'Mechanical Engineering',
        'architecture'   => 'Architecture & Urban Planning',
        'energy'         => 'Energy, Oil, Gas & Utilities',
        'mining'         => 'Mining & Quarrying',
        'manufacturing'  => 'Manufacturing & Production',
        'automotive'     => 'Automotive',
        'aviation'       => 'Aviation & Aerospace',
        'logistics'      => 'Logistics & Supply Chain',
        'procurement'    => 'Procurement & Purchasing',
        'transport'      => 'Transport, Shipping & Driving',
        'trades'         => 'Skilled Trades, Technicians & Maintenance',
        'health'         => 'Healthcare & Medical',
        'science'        => 'Science & Research',
        'biotech'        => 'Biotechnology & Pharmaceuticals',
        'agriculture'    => 'Agriculture, Forestry & Fishing',
        'environment'    => 'Environment, Health & Safety',
        'education'      => 'Education & Training',
        'ngo'            => 'NGO, Nonprofit & Social Services',
        'government'     => 'Government & Public Administration',
        'security'       => 'Security, Defence & Public Safety',
        'legal'          => 'Legal & Compliance',
        'hr'             => 'Human Resources & Recruitment',
        'consulting'     => 'Consulting & Advisory',
        'management'     => 'Management & Executive',
        'project'        => 'Project & Programme Management',
        'operations'     => 'Business Operations & Coordination',
        'design'         => 'Design & Creative Arts',
        'media'          => 'Media, Publishing & Printing',
        'realestate'     => 'Real Estate & Property',
        'retail'         => 'Retail & Consumer Goods',
        'hospitality'    => 'Hospitality, Tourism & Catering',
        'sports'         => 'Sports, Fitness & Wellness',
        'internships'    => 'Internships & Graduate Programmes',
        'parttime'       => 'Part-time, Freelance & Contract',
        'tenders'        => 'Tenders & Procurement Notices',
        'other'          => 'General & Multiple Vacancies',
    ];

    // old sort_order => taxonomy key
    private const MAP = [
        1 => 'accounting', 2 => 'admin', 3 => 'marketing', 4 => 'aviation', 5 => 'architecture',
        6 => 'automotive', 7 => 'banking', 8 => 'biotech', 9 => 'civil', 10 => 'engineering',
        11 => 'security', 12 => 'marketing', 13 => 'it', 14 => 'civil', 15 => 'consulting',
        16 => 'customer', 17 => 'security', 18 => 'design', 19 => 'education', 20 => 'electrical',
        21 => 'electrical', 22 => 'energy', 24 => 'environment', 25 => 'ngo', 26 => 'health',
        27 => 'security', 28 => 'hr', 29 => 'insurance', 30 => 'security', 31 => 'internships',
        32 => 'legal', 33 => 'logistics', 34 => 'trades', 35 => 'management', 36 => 'manufacturing',
        37 => 'marketing', 38 => 'procurement', 39 => 'mechanical', 40 => 'realestate',
        41 => 'security', 42 => 'parttime', 43 => 'media', 44 => 'design', 45 => 'marketing',
        46 => 'security', 47 => 'data', 48 => 'retail', 49 => 'sales', 50 => 'science',
        51 => 'transport', 52 => 'trades', 53 => 'trades', 54 => 'transport', 55 => 'transport',
        56 => 'software', 57 => 'agriculture', 58 => 'other', 59 => 'ngo', 60 => 'internships',
        61 => 'government', 62 => 'operations', 63 => 'operations', 64 => 'operations',
        65 => 'management', 66 => 'management', 67 => 'management', 68 => 'management',
        69 => 'management', 70 => 'tenders', 71 => 'hospitality',
    ];

    public function handle()
    {
        if (!Schema::hasColumn(self::JOBS_TABLE, self::JOBS_FK)) {
            $this->error(self::JOBS_TABLE . '.' . self::JOBS_FK . ' not found. Fix JOBS_FK at the top of this file.');
            return 1;
        }

        $dry   = $this->option('dry-run');
        $codes = $this->argument('country') ? [strtoupper($this->argument('country'))] : array_keys(self::COUNTRIES);
        $redirects = [];

        foreach ($codes as $cc) {
            if (!isset(self::COUNTRIES[$cc])) { $this->error("Unknown country $cc"); continue; }
            $country = self::COUNTRIES[$cc];
            $this->info("== {$country} ({$cc}) ==");

            DB::beginTransaction();
            try {
                $existing = JobCategory::where('country_code', $cc)->orderBy('id')->get();
                $oldSlugs = $existing->pluck('slug', 'id')->all();
                $groups = [];
                foreach ($existing as $c) {
                    $key = self::MAP[$c->sort_order] ?? null;
                    if ($key === null) {
                        $this->warn("  Unmapped, left untouched: id={$c->id} \"{$c->name}\" (sort {$c->sort_order})");
                        continue;
                    }
                    $groups[$key][] = $c;
                }

                // Phase 1: free up slugs to avoid unique-constraint clashes mid-run
                foreach ($groups as $rows) {
                    foreach ($rows as $c) { $c->update(['slug' => 'tmp-' . $c->id]); }
                }

                // Phase 2: assign survivors, merge duplicates
                $order = 0; $created = $renamed = $merged = $moved = 0;
                foreach (self::TAXONOMY as $key => $name) {
                    $order++;
                    $slug = Str::slug("{$name}-jobs-in-{$country}-{$cc}");
                    $data = [
                        'name'             => "{$name} jobs in {$country}",
                        'slug'             => $slug,
                        'country_code'     => $cc,
                        'meta_title'       => "{$name} Jobs in {$country} | Latest Vacancies",
                        'meta_description' => "Browse the latest {$name} vacancies in {$country}. Compare roles, employers and deadlines, then apply online.",
                        'color'            => 'primary',
                        'is_active'        => true,
                        'is_default'       => $order === 1,
                        'sort_order'       => $order,
                    ];

                    $rows = $groups[$key] ?? [];
                    $survivor = array_shift($rows);

                    if ($survivor) {
                        $survivor->update($data);
                        $renamed++;
                        $redirects[] = [$oldSlugs[$survivor->id], $slug];
                    } else {
                        $survivor = JobCategory::create($data + ['description' => null, 'icon' => null]);
                        $created++;
                    }

                    foreach ($rows as $dup) {
                        $n = DB::table(self::JOBS_TABLE)
                            ->where(self::JOBS_FK, $dup->id)
                            ->update([self::JOBS_FK => $survivor->id]);
                        $moved += $n;
                        $redirects[] = [$oldSlugs[$dup->id], $slug];
                        if ($this->option('keep-merged')) {
                            $dup->update(['is_active' => false, 'slug' => 'merged-' . $dup->id]);
                        } else {
                            $dup->delete();
                        }
                        $merged++;
                    }
                }

                $this->line("  renamed: {$renamed}, created: {$created}, merged: {$merged}, jobs moved: {$moved}");
                $dry ? DB::rollBack() : DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("  FAILED, rolled back: " . $e->getMessage());
            }
        }

        if ($dry) {
            $this->warn('Dry run: everything rolled back.');
        } elseif ($redirects) {
            $fh = fopen(storage_path('app/category-slug-redirects.csv'), 'w');
            fputcsv($fh, ['old_slug', 'new_slug']);
            foreach ($redirects as $r) { fputcsv($fh, $r); }
            fclose($fh);
            $this->info('Old-to-new slug map saved to storage/app/category-slug-redirects.csv');
        }
        return 0;
    }
}