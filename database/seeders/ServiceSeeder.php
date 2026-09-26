<?php

namespace Database\Seeders;

use App\Models\Service\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // =================================================================
            // JOB POSTING PACKAGES (one-time, per job)
            // =================================================================
            [
                'key'                      => 'job_post_free',
                'name'                     => 'Free Post',
                'description'              => 'Standard 14-day job listing, search-engine accessible. Just getting started.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 200,
                'meta'                     => [
                    'family'               => 'job_posting',
                    'tagline'              => 'Just getting started',
                    'badge'                => null,
                    'listing_days'         => 14,
                    'is_featured'          => false,
                    'is_urgent'            => false,
                    'featured_days'        => 0,
                    'whatsapp_distribution'=> 'none',
                    'whatsapp_count'       => 0,
                    'email_alerts'         => 'none',
                    'email_alert_count'    => 0,
                    'popup_devices'        => [],
                    'popup_views'          => 0,
                    'priority_placement'   => false,
                    'paid_ads_boost'       => false,
                    'enterprise_ats'       => false,
                ],
            ],
            [
                'key'                      => 'job_post_standard',
                'name'                     => 'Budget Package',
                'description'              => 'Distributed via email alerts and WhatsApp groups thrice. Search-engine accessible.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 210,
                'meta'                     => [
                    'family'               => 'job_posting',
                    'tagline'              => 'Budget Conscious',
                    'badge'                => null,
                    'listing_days'         => 30,
                    'is_featured'          => false,
                    'is_urgent'            => false,
                    'featured_days'        => 0,
                    'whatsapp_distribution'=> 'limited',
                    'whatsapp_count'       => 3,
                    'email_alerts'         => 'limited',
                    'email_alert_count'    => 3,
                    'popup_devices'        => ['desktop'],
                    'popup_views'          => 3,
                    'priority_placement'   => false,
                    'paid_ads_boost'       => false,
                    'enterprise_ats'       => false,
                ],
            ],
            [
                'key'                      => 'job_post_popular',
                'name'                     => 'Premium Package',
                'description'              => 'Unlimited WhatsApp distribution, email alerts until deadline, pop-up on mobile and desktop.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 220,
                'meta'                     => [
                    'family'               => 'job_posting',
                    'tagline'              => 'Popular Choice',
                    'badge'                => 'popular',
                    'listing_days'         => 30,
                    'is_featured'          => false,
                    'is_urgent'            => false,
                    'featured_days'        => 0,
                    'whatsapp_distribution'=> 'unlimited',
                    'whatsapp_count'       => null,
                    'email_alerts'         => 'unlimited',
                    'email_alert_count'    => null,
                    'popup_devices'        => ['desktop', 'mobile'],
                    'popup_views'          => null,
                    'priority_placement'   => false,
                    'paid_ads_boost'       => false,
                    'enterprise_ats'       => false,
                ],
            ],
            [
                'key'                      => 'job_post_priority',
                'name'                     => 'Timely — Premium + Priority',
                'description'              => 'All Premium features plus priority distribution, top placement, and paid ad platform promotion.',
                'default_turnaround_hours' => 12,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 230,
                'meta'                     => [
                    'family'               => 'job_posting',
                    'tagline'              => 'Brand Conscious',
                    'badge'                => 'urgent',
                    'listing_days'         => 30,
                    'is_featured'          => true,
                    'is_urgent'            => true,
                    'featured_days'        => 30,
                    'whatsapp_distribution'=> 'unlimited',
                    'whatsapp_count'       => null,
                    'email_alerts'         => 'unlimited',
                    'email_alert_count'    => null,
                    'popup_devices'        => ['desktop', 'mobile'],
                    'popup_views'          => null,
                    'priority_placement'   => true,
                    'paid_ads_boost'       => true,
                    'enterprise_ats'       => false,
                ],
            ],
            [
                'key'                      => 'job_post_enterprise',
                'name'                     => 'Enterprise',
                'description'              => 'Enterprise Applicant Tracking System plus every premium and priority feature.',
                'default_turnaround_hours' => 12,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 240,
                'meta'                     => [
                    'family'               => 'job_posting',
                    'tagline'              => 'System Driven',
                    'badge'                => 'enterprise',
                    'listing_days'         => 60,
                    'is_featured'          => true,
                    'is_urgent'            => true,
                    'featured_days'        => 60,
                    'whatsapp_distribution'=> 'unlimited',
                    'whatsapp_count'       => null,
                    'email_alerts'         => 'unlimited',
                    'email_alert_count'    => null,
                    'popup_devices'        => ['desktop', 'mobile'],
                    'popup_views'          => null,
                    'priority_placement'   => true,
                    'paid_ads_boost'       => true,
                    'enterprise_ats'       => true,
                ],
            ],

            // =================================================================
            // CV SERVICES — ONE-TIME
            // =================================================================
            [
                'key'                      => 'cv_review',
                'name'                     => 'CV Review',
                'description'              => 'Get a detailed professional review of your CV with actionable feedback on structure, content, grammar, and ATS compatibility.',
                'default_turnaround_hours' => 48,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 10,
                'meta'                     => [
                    'family'             => 'cv_service',
                    'tagline'            => 'Professional Feedback',
                    'badge'              => null,
                    'icon'               => 'ki-document',
                    'category'           => 'review',
                    'delivery_method'    => 'pdf_report',      // pdf_report | rewritten_file | live_session | digital_access
                    'includes_files'     => ['review_report'],
                    'revisions_allowed'  => 1,
                    'requires_cv_upload' => true,
                    'requires_job_target'=> false,
                    'production_steps'   => ['ai_gap_review', 'human_review', 'pdf_generation'],
                    'skills_focus'       => ['ats', 'structure', 'content'],
                ],
            ],
            [
                'key'                      => 'cv_rewrite',
                'name'                     => 'CV Rewrite',
                'description'              => 'Complete rewrite of your CV by a certified career expert, optimized for applicant tracking systems and your target role.',
                'default_turnaround_hours' => 72,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 20,
                'meta'                     => [
                    'family'             => 'cv_service',
                    'tagline'            => 'Full Rewrite by Expert',
                    'badge'              => 'popular',
                    'icon'               => 'ki-pencil',
                    'category'           => 'rewrite',
                    'delivery_method'    => 'rewritten_file',
                    'includes_files'     => ['review_report', 'rewritten_cv'],
                    'revisions_allowed'  => 2,
                    'requires_cv_upload' => true,
                    'requires_job_target'=> false,
                    'production_steps'   => ['ai_gap_review', 'seeker_answers', 'human_rewrite', 'delivery'],
                    'skills_focus'       => ['ats', 'structure', 'content', 'tailoring'],
                ],
            ],
            [
                'key'                      => 'cover_letter',
                'name'                     => 'Cover Letter Writing',
                'description'              => 'A tailored, professionally written cover letter customized for a specific job application.',
                'default_turnaround_hours' => 48,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 30,
                'meta'                     => [
                    'family'             => 'cv_service',
                    'tagline'            => 'Tailored to Your Target Job',
                    'badge'              => null,
                    'icon'               => 'ki-message-text-2',
                    'category'           => 'cover_letter',
                    'delivery_method'    => 'rewritten_file',
                    'includes_files'     => ['cover_letter'],
                    'revisions_allowed'  => 1,
                    'requires_cv_upload' => true,
                    'requires_job_target'=> true,     // needs the job description to tailor
                    'production_steps'   => ['target_job_analysis', 'human_write', 'delivery'],
                    'skills_focus'       => ['tailoring', 'persuasion', 'professional_writing'],
                ],
            ],
            [
                'key'                      => 'linkedin_optimization',
                'name'                     => 'LinkedIn Profile Optimization',
                'description'              => 'Full optimization of your LinkedIn profile to attract recruiters and improve visibility in search results.',
                'default_turnaround_hours' => 72,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 40,
                'meta'                     => [
                    'family'             => 'cv_service',
                    'tagline'            => 'Get Noticed by Recruiters',
                    'badge'              => null,
                    'icon'               => 'ki-linkedin',
                    'category'           => 'linkedin',
                    'delivery_method'    => 'rewritten_file',
                    'includes_files'     => ['linkedin_optimization_guide'],
                    'revisions_allowed'  => 1,
                    'requires_cv_upload' => true,
                    'requires_job_target'=> false,
                    'production_steps'   => ['profile_analysis', 'optimization', 'delivery'],
                    'skills_focus'       => ['seo', 'personal_branding', 'keyword_optimization'],
                ],
            ],
            [
                'key'                      => 'interview_coaching',
                'name'                     => '1-on-1 Interview Coaching',
                'description'              => 'A 60-minute live coaching session with a career expert to prepare for your upcoming interview.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'one_time',
                'is_active'                => true,
                'sort_order'               => 50,
                'meta'                     => [
                    'family'             => 'cv_service',
                    'tagline'            => 'Live Expert Session',
                    'badge'              => null,
                    'icon'               => 'ki-messages',
                    'category'           => 'interview',
                    'delivery_method'    => 'live_session',
                    'includes_files'     => [],
                    'session_minutes'    => 60,
                    'session_platform'   => 'zoom',   // zoom | google_meet | whatsapp_video
                    'revisions_allowed'  => 0,
                    'requires_cv_upload' => true,
                    'requires_job_target'=> true,
                    'production_steps'   => ['session_booking', 'session_delivery'],
                    'skills_focus'       => ['interview_prep', 'communication', 'confidence'],
                ],
            ],

            // =================================================================
            // SUBSCRIPTION SERVICES
            // =================================================================
            [
                'key'                      => 'premium_alerts',
                'name'                     => 'Premium Job Alerts',
                'description'              => 'Receive instant notifications for new job openings matching your criteria — days before they appear on public boards.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 60,
                'meta'                     => [
                    'family'             => 'job_seeker_subscription',
                    'tagline'            => 'Be First to Know',
                    'badge'              => 'popular',
                    'icon'               => 'ki-notification-2',
                    'channels'           => ['email', 'whatsapp'],   // communication channels
                    'frequency'          => 'instant',                // instant | daily | weekly
                    'max_alerts_per_day' => null,
                    'features'           => ['priority_match', 'early_access'],
                    'interval'           => 'month',
                    'trial_days'         => 7,
                ],
            ],
            [
                'key'                      => 'featured_applicant',
                'name'                     => 'Featured Applicant',
                'description'              => 'Your profile is highlighted to recruiters for 30 days, boosting your visibility and response rate.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 70,
                'meta'                     => [
                    'family'             => 'job_seeker_subscription',
                    'tagline'            => 'Stand Out to Recruiters',
                    'badge'              => null,
                    'icon'               => 'ki-star',
                    'channels'           => [],
                    'frequency'          => 'continuous',
                    'features'           => ['profile_highlight', 'recruiter_visibility'],
                    'duration_days'      => 30,
                    'interval'           => 'month',
                    'trial_days'         => 0,
                ],
            ],
            [
                'key'                      => 'career_mentorship',
                'name'                     => 'Monthly Career Mentorship',
                'description'              => 'Ongoing mentorship with a senior career coach — two 1-hour sessions per month plus email support.',
                'default_turnaround_hours' => 24,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 80,
                'meta'                     => [
                    'family'             => 'job_seeker_subscription',
                    'tagline'            => 'Personal Career Coach',
                    'badge'              => null,
                    'icon'               => 'ki-profile-user',
                    'channels'           => ['email', 'video_call'],
                    'frequency'          => '2x_per_month',
                    'session_minutes'    => 60,
                    'sessions_per_month' => 2,
                    'features'           => ['mentorship', 'accountability', 'career_planning'],
                    'interval'           => 'month',
                    'trial_days'         => 0,
                ],
            ],
            [
                'key'                      => 'recruiter_access',
                'name'                     => 'Recruiter Direct Access',
                'description'              => 'Your profile is shared with verified recruiters on our partner network, with priority ranking in search.',
                'default_turnaround_hours' => 12,
                'billing_type'             => 'subscription',
                'is_active'                => true,
                'sort_order'               => 90,
                'meta'                     => [
                    'family'             => 'job_seeker_subscription',
                    'tagline'            => 'Be Discovered Directly',
                    'badge'              => null,
                    'icon'               => 'ki-share',
                    'channels'           => ['recruiter_network'],
                    'frequency'          => 'continuous',
                    'features'           => ['priority_search_ranking', 'recruiter_direct_share'],
                    'interval'           => 'month',
                    'trial_days'         => 7,
                ],
            ],

            // =================================================================
            // FREE SERVICES
            // =================================================================
            [
                'key'                      => 'basic_job_alerts',
                'name'                     => 'Basic Job Alerts',
                'description'              => 'Weekly email digest of new job openings matching your saved search criteria. Free forever.',
                'default_turnaround_hours' => 168, // 7 days
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 100,
                'meta'                     => [
                    'family'      => 'free_service',
                    'tagline'     => 'Free Forever',
                    'badge'       => null,
                    'icon'        => 'ki-notification-status',
                    'channels'    => ['email'],
                    'frequency'   => 'weekly',
                    'features'    => ['basic_matching', 'weekly_digest'],
                ],
            ],
            [
                'key'                      => 'resume_builder',
                'name'                     => 'Free Resume Builder',
                'description'              => 'Access to our online resume builder with modern templates and real-time preview. Free for all registered users.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 110,
                'meta'                     => [
                    'family'          => 'free_service',
                    'tagline'         => 'Free Forever',
                    'badge'           => null,
                    'icon'            => 'ki-document',
                    'features'        => ['templates', 'real_time_preview', 'export_pdf'],
                    'template_count'  => 5,
                    'export_formats'  => ['pdf', 'docx'],
                ],
            ],
            [
                'key'                      => 'salary_calculator',
                'name'                     => 'Salary Calculator',
                'description'              => 'Estimate your market value based on role, experience, location, and industry benchmarks.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 120,
                'meta'                     => [
                    'family'      => 'free_service',
                    'tagline'     => 'Free Forever',
                    'badge'       => null,
                    'icon'        => 'ki-chart-simple',
                    'features'    => ['benchmark_data', 'industry_breakdown', 'country_adjusted'],
                    'data_source' => 'market_survey',
                ],
            ],
            [
                'key'                      => 'career_resources',
                'name'                     => 'Career Resources Library',
                'description'              => 'Free access to our library of articles, guides, templates, and video tutorials for job seekers.',
                'default_turnaround_hours' => 1,
                'billing_type'             => 'free',
                'is_active'                => true,
                'sort_order'               => 130,
                'meta'                     => [
                    'family'          => 'free_service',
                    'tagline'         => 'Free Forever',
                    'badge'           => null,
                    'icon'            => 'ki-book-open',
                    'features'        => ['articles', 'guides', 'templates', 'videos'],
                    'content_types'   => ['article', 'guide', 'template', 'video'],
                ],
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($services as $service) {
            $existing = Service::where('key', $service['key'])->first();

            Service::updateOrCreate(
                ['key' => $service['key']],
                $service
            );

            $existing ? $updated++ : $created++;
        }

        $this->command->info("✅ Seeded " . count($services) . " services ({$created} created, {$updated} updated).");

        // Breakdown by billing type
        $byType = collect($services)->groupBy('billing_type')->map->count();
        foreach ($byType as $type => $count) {
            $this->command->info("   → {$type}: {$count}");
        }

        // Breakdown by family
        $byFamily = collect($services)
            ->groupBy(fn($s) => $s['meta']['family'] ?? 'unclassified')
            ->map->count();
        foreach ($byFamily as $family => $count) {
            $this->command->info("   → {$family}: {$count}");
        }
    }
}