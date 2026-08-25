<?php

namespace Database\Seeders;

use App\Models\Job\JobType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting Job Type Seeder...');
        $this->command->newLine();

        // Using only verified working Metronic icons
        $types = [
            ['name' => 'Full-time', 'icon' => 'ki-briefcase', 'sort_order' => 1],
            ['name' => 'Part-time', 'icon' => 'ki-clock', 'sort_order' => 2],
            ['name' => 'Contract', 'icon' => 'ki-file', 'sort_order' => 3],
            ['name' => 'Temporary', 'icon' => 'ki-calendar', 'sort_order' => 4],
            ['name' => 'Internship', 'icon' => 'ki-education', 'sort_order' => 5],
            ['name' => 'Freelance', 'icon' => 'ki-laptop', 'sort_order' => 6],
            ['name' => 'Remote', 'icon' => 'ki-home', 'sort_order' => 7],
            ['name' => 'Hybrid', 'icon' => 'ki-building', 'sort_order' => 8],
            ['name' => 'Shift', 'icon' => 'ki-night', 'sort_order' => 9],
            ['name' => 'Volunteer', 'icon' => 'ki-heart', 'sort_order' => 10],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($types as $data) {
            $slug = Str::slug($data['name']);
            
            $exists = JobType::where('slug', $slug)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            JobType::create([
                'name' => $data['name'],
                'slug' => $slug,
                'icon' => $data['icon'],
                'description' => "{$data['name']} employment opportunities. Find {$data['name']} jobs and career positions.",
                'meta_title' => "{$data['name']} Jobs - Employment Opportunities",
                'meta_description' => "Find {$data['name']} jobs and employment opportunities. Browse career positions across various industries.",
                'is_active' => true,
                'sort_order' => $data['sort_order'],
            ]);
            
            $created++;
        }

        $this->command->info("✅ Job Type Seeder completed: {$created} created, {$skipped} skipped.");
        $this->command->newLine();
        
        // Show summary
        $this->command->info('📊 Job Types Summary:');
        $this->command->newLine();
        
        $summary = [];
        $types = JobType::orderBy('sort_order')->get();
        foreach ($types as $type) {
            $summary[] = [
                $type->id,
                $type->name,
                $type->icon ? '✅' : '❌',
                $type->is_active ? '✅ Active' : '❌ Inactive',
            ];
        }
        
        $this->command->table(
            ['ID', 'Name', 'Icon', 'Status'],
            $summary
        );
    }
}