<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;  // Add this import

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'IT and Systems Administration',
                'code' => 'IT',
                'slug' => 'it-and-systems-administration',
                'description' => 'Responsible for all IT infrastructure, systems, and technical support',
                'icon' => 'ki-computer',
                'color' => 'primary',
                'sort_order' => 1,
                'email' => 'it@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'slug' => 'human-resources',
                'description' => 'Manages employee relations, recruitment, and personnel matters',
                'icon' => 'ki-users',
                'color' => 'success',
                'sort_order' => 2,
                'email' => 'hr@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Operations Management',
                'code' => 'OPS',
                'slug' => 'operations-management',
                'description' => 'Oversees daily operations and business processes',
                'icon' => 'ki-chart-line',
                'color' => 'info',
                'sort_order' => 3,
                'email' => 'operations@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Business Development',
                'code' => 'BD',
                'slug' => 'business-development',
                'description' => 'Responsible for growth, partnerships, and new opportunities',
                'icon' => 'ki-chart-pie',
                'color' => 'warning',
                'sort_order' => 4,
                'email' => 'bd@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Maids Department',
                'code' => 'MAID',
                'slug' => 'maids-department',
                'description' => 'Manages maid recruitment, training, and placements',
                'icon' => 'ki-home',
                'color' => 'danger',
                'sort_order' => 5,
                'email' => 'maids@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Job Posting Department',
                'code' => 'JOB',
                'slug' => 'job-posting-department',
                'description' => 'Handles job postings, employer listings, and job boards',
                'icon' => 'ki-briefcase',
                'color' => 'primary',
                'sort_order' => 6,
                'email' => 'jobs@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Casual Work Management',
                'code' => 'CWM',
                'slug' => 'casual-work-management',
                'description' => 'Manages casual workers, daily labor, and temporary assignments',
                'icon' => 'ki-clock',
                'color' => 'info',
                'sort_order' => 7,
                'email' => 'casual@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Workforce Management',
                'code' => 'WFM',
                'slug' => 'workforce-management',
                'description' => 'Oversees workforce planning, scheduling, and optimization',
                'icon' => 'ki-people',
                'color' => 'success',
                'sort_order' => 8,
                'email' => 'workforce@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Finance and Accounting',
                'code' => 'FIN',
                'slug' => 'finance-and-accounting',
                'description' => 'Manages financial operations, budgets, and accounting',
                'icon' => 'ki-dollar',
                'color' => 'warning',
                'sort_order' => 9,
                'email' => 'finance@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Customer Support',
                'code' => 'CS',
                'slug' => 'customer-support',
                'description' => 'Handles customer inquiries, support tickets, and client relations',
                'icon' => 'ki-headphones',
                'color' => 'secondary',
                'sort_order' => 10,
                'email' => 'support@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Recruitment',
                'code' => 'REC',
                'slug' => 'recruitment',
                'description' => 'Manages recruitment services for clients',
                'icon' => 'ki-user-search',
                'color' => 'primary',
                'sort_order' => 11,
                'email' => 'recruitment@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'Consultancy',
                'code' => 'CONS',
                'slug' => 'consultancy',
                'description' => 'Provides HR and business consultancy services',
                'icon' => 'ki-chart-simple',
                'color' => 'info',
                'sort_order' => 12,
                'email' => 'consultancy@lafab.com',
                'is_active' => true,
            ],
            [
                'name' => 'CV Shortlisting',
                'code' => 'CVS',
                'slug' => 'cv-shortlisting',
                'description' => 'Specializes in CV screening and candidate shortlisting',
                'icon' => 'ki-document',
                'color' => 'success',
                'sort_order' => 13,
                'email' => 'cvs@lafab.com',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        $this->command->info('Departments seeded successfully!');
    }
}