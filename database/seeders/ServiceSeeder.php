<?php

namespace Database\Seeders;

use App\Models\Service\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // -------------------------------------------------------
            // ONE-TIME SERVICES
            // -------------------------------------------------------
            [
                'key'                      => 'cv_review',
                'name'                     => 'CV Review',
                'description'              => 'Get a detailed professional review of your CV with actionable feedback on structure, content, grammar, and ATS compatibility.',
                'default_turnaround_hours' => 48,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 10,
            ],
            [
                'key'                      => 'cv_rewrite',
                'name'                     => 'CV Rewrite',
                'description'              => 'Complete rewrite of your CV by a certified career expert, optimized for applicant tracking systems and your target role.',
                'default_turnaround_hours' => 72,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 20,
            ],
            [
                'key'                      => 'cover_letter',
                'name'                     => 'Cover Letter Writing',
                'description'              => 'A tailored, professionally written cover letter customized for a specific job application.',
                'default_turnaround_hours' => 48,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 30,
            ],
            [
                'key'                      => 'linkedin_optimization',
                'name'                     => 'LinkedIn Profile Optimization',
                'description'              => 'Full optimization of your LinkedIn profile to attract recruiters and improve visibility in search results.',
                'default_turnaround_hours' => 72,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 40,
            ],
            [
                'key'                      => 'interview_coaching',
                'name'                     => '1-on-1 Interview Coaching',
                'description'              => 'A 60-minute live coaching session with a career expert to prepare for your upcoming interview.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 50,
            ],

            // -------------------------------------------------------
            // SUBSCRIPTION SERVICES
            // -------------------------------------------------------
            [
                'key'                      => 'premium_alerts',
                'name'                     => 'Premium Job Alerts',
                'description'              => 'Receive instant notifications for new job openings matching your criteria — days before they appear on public boards.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 60,
            ],
            [
                'key'                      => 'featured_applicant',
                'name'                     => 'Featured Applicant',
                'description'              => 'Your profile is highlighted to recruiters for 30 days, boosting your visibility and response rate.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 70,
            ],
            [
                'key'                      => 'career_mentorship',
                'name'                     => 'Monthly Career Mentorship',
                'description'              => 'Ongoing mentorship with a senior career coach — two 1-hour sessions per month plus email support.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 80,
            ],
            [
                'key'                      => 'recruiter_access',
                'name'                     => 'Recruiter Direct Access',
                'description'              => 'Your profile is shared with verified recruiters on our partner network, with priority ranking in search.',
                'default_turnaround_hours' => 12,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 90,
            ],

            // -------------------------------------------------------
            // FREE SERVICES
            // -------------------------------------------------------
            [
                'key'                      => 'basic_job_alerts',
                'name'                     => 'Basic Job Alerts',
                'description'              => 'Weekly email digest of new job openings matching your saved search criteria. Free forever.',
                'default_turnaround_hours' => 168, // 7 days
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 100,
            ],
            [
                'key'                      => 'resume_builder',
                'name'                     => 'Free Resume Builder',
                'description'              => 'Access to our online resume builder with modern templates and real-time preview. Free for all registered users.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 110,
            ],
            [
                'key'                      => 'salary_calculator',
                'name'                     => 'Salary Calculator',
                'description'              => 'Estimate your market value based on role, experience, location, and industry benchmarks.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 120,
            ],
            [
                'key'                      => 'career_resources',
                'name'                     => 'Career Resources Library',
                'description'              => 'Free access to our library of articles, guides, templates, and video tutorials for job seekers.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 130,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['key' => $service['key']],
                $service
            );
        }

        $this->command->info('✅ Seeded ' . count($services) . ' services (' 
            . collect($services)->where('billing_type', 'one_time')->count() . ' one-time, '
            . collect($services)->where('billing_type', 'subscription')->count() . ' subscription, '
            . collect($services)->where('billing_type', 'free')->count() . ' free).');
    }
}