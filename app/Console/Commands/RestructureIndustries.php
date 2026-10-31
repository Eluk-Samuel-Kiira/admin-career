<?php

namespace App\Console\Commands;

use App\Models\Job\Industry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestructureIndustries extends Command
{
    protected $signature = 'industries:restructure
                            {--dry-run : Run everything, then roll back}
                            {--keep-merged : Deactivate merged duplicates instead of deleting them}';

    protected $description = 'Rename industries to a standard taxonomy, keeping existing IDs';

    // key => display name. Order here = sort_order.
    private const TAXONOMY = [
        'agriculture'   => 'Agriculture, Forestry & Fishing',
        'mining'        => 'Mining & Quarrying',
        'oilgas'        => 'Oil, Gas & Petroleum',
        'energy'        => 'Energy & Power Generation',
        'renewable'     => 'Renewable Energy & Clean Technology',
        'utilities'     => 'Electricity, Water & Utilities',
        'foodbev'       => 'Food & Beverage Production',
        'textiles'      => 'Textiles, Apparel & Fashion',
        'wood'          => 'Paper, Wood & Packaging',
        'chemicals'     => 'Chemicals, Plastics & Materials',
        'pharma'        => 'Pharmaceuticals & Biotechnology',
        'machinery'     => 'Machinery & Industrial Equipment',
        'electronics'   => 'Electronics & Semiconductors',
        'automotive'    => 'Automotive & Motor Vehicles',
        'aerospace'     => 'Aerospace & Defence',
        'manufacturing' => 'General Manufacturing',
        'construction'  => 'Building & Construction',
        'civileng'      => 'Civil Engineering & Infrastructure',
        'engineering'   => 'Engineering Services',
        'architecture'  => 'Architecture, Planning & Landscaping',
        'realestate'    => 'Real Estate & Property',
        'wholesale'     => 'Wholesale, Import & Export',
        'retail'        => 'Retail & Sales',
        'ecommerce'     => 'E-commerce & Online Marketplaces',
        'consumer'      => 'Consumer Goods & FMCG',
        'cosmetics'     => 'Beauty, Cosmetics & Personal Care',
        'furniture'     => 'Furniture & Home Goods',
        'transport'     => 'Transportation, Freight & Logistics',
        'aviation'      => 'Aviation & Airlines',
        'maritime'      => 'Maritime, Ports & Shipping',
        'warehousing'   => 'Warehousing & Supply Chain',
        'it'            => 'IT Services & Consulting',
        'software'      => 'Software & Internet Services',
        'telecom'       => 'Telecommunications & Wireless',
        'hardware'      => 'Computer Hardware & Networking',
        'robotics'      => 'Robotics, Automation & AI',
        'media'         => 'Media, Broadcasting & Publishing',
        'entertainment' => 'Film, Music & Entertainment',
        'gaming'        => 'Gaming & Esports',
        'advertising'   => 'Marketing, Advertising & Public Relations',
        'design'        => 'Design & Creative Services',
        'banking'       => 'Banking & Microfinance',
        'insurance'     => 'Insurance',
        'investment'    => 'Investment & Asset Management',
        'financial'     => 'Financial Services & Fintech',
        'accounting'    => 'Accounting, Audit & Tax',
        'legal'         => 'Legal Services',
        'consulting'    => 'Management & Professional Consulting',
        'hr'            => 'Staffing, Recruitment & HR Services',
        'bizservices'   => 'Business Support & Outsourcing',
        'education'     => 'Education & Training',
        'highered'      => 'Universities & Higher Education',
        'research'      => 'Scientific Research & Development',
        'healthcare'    => 'Healthcare, Hospitals & Clinics',
        'meddevices'    => 'Medical Devices & Equipment',
        'veterinary'    => 'Veterinary & Animal Care',
        'social'        => 'Social Services & Community Development',
        'government'    => 'Government & Public Administration',
        'ngo'           => 'NGOs, Charities & Foundations',
        'security'      => 'Security & Law Enforcement',
        'safety'        => 'Emergency Services & Public Safety',
        'environmental' => 'Environmental Services & Waste Management',
        'hospitality'   => 'Hotels & Accommodation',
        'restaurants'   => 'Restaurants, Catering & Food Service',
        'tourism'       => 'Travel, Tourism & Events',
        'sports'        => 'Sports, Fitness & Recreation',
        'arts'          => 'Arts, Culture & Museums',
    ];

    // old industry name => taxonomy key
    private const MAP = [
        'Accounting' => 'accounting', 'Aerospace' => 'aerospace', 'Agriculture' => 'agriculture',
        'Airlines' => 'aviation', 'Apparel' => 'textiles', 'Architecture' => 'architecture',
        'Arts' => 'arts', 'Automotive' => 'automotive', 'Banking' => 'banking',
        'Biotechnology' => 'pharma', 'Broadcasting' => 'media', 'Business Services' => 'bizservices',
        'Chemical' => 'chemicals', 'Civil Engineering' => 'civileng',
        'Commercial Real Estate' => 'realestate', 'Communications' => 'advertising',
        'Computer Hardware' => 'hardware', 'Computer Networking' => 'hardware',
        'Computer Software' => 'software', 'Construction' => 'construction',
        'Consulting' => 'consulting', 'Consumer Electronics' => 'electronics',
        'Consumer Goods' => 'consumer', 'Cosmetics' => 'cosmetics', 'Dairy' => 'foodbev',
        'Defense' => 'aerospace', 'Design' => 'design', 'E-commerce' => 'ecommerce',
        'Education' => 'education', 'Electrical Engineering' => 'engineering',
        'Electronics' => 'electronics', 'Energy' => 'energy', 'Engineering' => 'engineering',
        'Entertainment' => 'entertainment', 'Environmental Services' => 'environmental',
        'Event Services' => 'tourism', 'Fashion' => 'textiles', 'Film' => 'entertainment',
        'Financial Services' => 'financial', 'Fine Art' => 'arts', 'Fishery' => 'agriculture',
        'Food Production' => 'foodbev', 'Food Services' => 'restaurants', 'Forestry' => 'agriculture',
        'Furniture' => 'furniture', 'Gaming' => 'gaming', 'Government' => 'government',
        'Graphic Design' => 'design', 'Health and Human Services' => 'social',
        'Healthcare' => 'healthcare', 'Higher Education' => 'highered', 'Horticulture' => 'agriculture',
        'Hospitality' => 'hospitality', 'Human Resources' => 'hr', 'Information Technology' => 'it',
        'Insurance' => 'insurance', 'Interior Design' => 'design', 'International Trade' => 'wholesale',
        'Internet' => 'software', 'Investment Banking' => 'investment',
        'Investment Management' => 'investment', 'Journalism' => 'media',
        'Landscaping' => 'architecture', 'Law Enforcement' => 'security', 'Law Practice' => 'legal',
        'Legal Services' => 'legal', 'Leisure' => 'sports', 'Logistics' => 'transport',
        'Machinery' => 'machinery', 'Management Consulting' => 'consulting',
        'Manufacturing' => 'manufacturing', 'Maritime' => 'maritime', 'Marketing' => 'advertising',
        'Mechanical Engineering' => 'engineering', 'Media' => 'media',
        'Medical Devices' => 'meddevices', 'Medical Practice' => 'healthcare',
        'Mental Health' => 'healthcare', 'Mining' => 'mining', 'Motion Pictures' => 'entertainment',
        'Museums' => 'arts', 'Music' => 'entertainment', 'Nanotechnology' => 'research',
        'Newspapers' => 'media', 'Nonprofit' => 'ngo', 'Nuclear' => 'energy', 'Oil and Gas' => 'oilgas',
        'Online Media' => 'media', 'Packaging' => 'wood', 'Paper' => 'wood',
        'Performing Arts' => 'arts', 'Pharmaceuticals' => 'pharma', 'Philanthropy' => 'ngo',
        'Photography' => 'design', 'Plastics' => 'chemicals', 'Political Organization' => 'government',
        'Printing' => 'media', 'Professional Services' => 'consulting', 'Public Relations' => 'advertising',
        'Public Safety' => 'safety', 'Publishing' => 'media', 'Railroad' => 'transport',
        'Real Estate' => 'realestate', 'Recreation' => 'sports', 'Recycling' => 'environmental',
        'Renewables' => 'renewable', 'Research' => 'research', 'Restaurants' => 'restaurants',
        'Retail' => 'retail', 'Robotics' => 'robotics', 'Safety' => 'safety', 'Sales' => 'retail',
        'Science' => 'research', 'Security' => 'security', 'Semiconductors' => 'electronics',
        'Shipbuilding' => 'maritime', 'Social Services' => 'social', 'Software Development' => 'software',
        'Sports' => 'sports', 'Staffing' => 'hr', 'Supply Chain' => 'warehousing',
        'Telecommunications' => 'telecom', 'Textiles' => 'textiles', 'Tourism' => 'tourism',
        'Transportation' => 'transport', 'Travel' => 'tourism', 'Utilities' => 'utilities',
        'Venture Capital' => 'investment', 'Veterinary' => 'veterinary', 'Warehousing' => 'warehousing',
        'Waste Management' => 'environmental', 'Water Services' => 'utilities', 'Wholesale' => 'wholesale',
        'Wireless' => 'telecom', 'Writing' => 'media',
    ];

    public function handle()
    {
        $dry = $this->option('dry-run');
        $industry = new Industry();
        $table = $industry->getTable();

        // Find every table that has an industry_id column (jobs, companies, pivots...)
        $refs = collect(DB::select(
            "SELECT TABLE_NAME t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'industry_id' AND TABLE_NAME <> ?",
            [$table]
        ))->pluck('t')->all();
        $this->info('Tables referencing industry_id: ' . ($refs ? implode(', ', $refs) : '(none found)'));

        $lookup = [];
        foreach (self::MAP as $old => $key) { $lookup[Str::slug($old)] = $key; }

        DB::beginTransaction();
        try {
            $existing = Industry::orderBy('id')->get();
            $oldSlugs = $existing->pluck('slug', 'id')->all();
            $groups = [];

            foreach ($existing as $row) {
                $key = $lookup[Str::slug($row->name)] ?? null;
                if ($key === null) {
                    $this->warn("Unmapped, left untouched: id={$row->id} \"{$row->name}\"");
                    continue;
                }
                $groups[$key][] = $row;
            }

            // Phase 1: free slugs to avoid unique clashes mid-run
            foreach ($groups as $rows) {
                foreach ($rows as $r) { $r->update(['slug' => 'tmp-' . $r->id]); }
            }

            // Phase 2: assign survivors, merge duplicates
            $order = 0; $created = $renamed = $merged = $moved = 0;
            $redirects = [];

            foreach (self::TAXONOMY as $key => $name) {
                $order++;
                $slug = Str::slug($name);
                $data = [
                    'name'             => $name,
                    'slug'             => $slug,
                    'description'      => "Explore jobs, vacancies and career opportunities in {$name}.",
                    'meta_title'       => "{$name} Jobs | Latest Vacancies",
                    'meta_description' => "Browse the latest {$name} vacancies. Compare roles, employers and deadlines, then apply online.",
                    'is_active'        => true,
                    'sort_order'       => $order,
                ];

                $rows = $groups[$key] ?? [];
                $survivor = array_shift($rows);

                if ($survivor) {
                    $survivor->update($data);
                    $renamed++;
                    $redirects[] = [$oldSlugs[$survivor->id], $slug];
                } else {
                    $survivor = Industry::create($data + ['icon' => null]);
                    $created++;
                }

                foreach ($rows as $dup) {
                    foreach ($refs as $t) {
                        // IGNORE skips rows that would violate a unique key (e.g. pivot tables)
                        $moved += DB::affectingStatement(
                            "UPDATE IGNORE `{$t}` SET industry_id = ? WHERE industry_id = ?",
                            [$survivor->id, $dup->id]
                        );
                        // anything left would be a duplicate link, so drop it
                        DB::table($t)->where('industry_id', $dup->id)->delete();
                    }
                    $redirects[] = [$oldSlugs[$dup->id], $slug];

                    if ($this->option('keep-merged')) {
                        $dup->update(['is_active' => false, 'slug' => 'merged-' . $dup->id]);
                    } else {
                        $dup->delete();
                    }
                    $merged++;
                }
            }

            $this->line("renamed: {$renamed}, created: {$created}, merged: {$merged}, references moved: {$moved}");
            $dry ? DB::rollBack() : DB::commit();

            if ($dry) {
                $this->warn('Dry run: everything rolled back.');
            } else {
                $fh = fopen(storage_path('app/industry-slug-redirects.csv'), 'w');
                fputcsv($fh, ['old_slug', 'new_slug']);
                foreach ($redirects as $r) { fputcsv($fh, $r); }
                fclose($fh);
                $this->info('Old-to-new slug map saved to storage/app/industry-slug-redirects.csv');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('FAILED, rolled back: ' . $e->getMessage());
            return 1;
        }
        return 0;
    }
}