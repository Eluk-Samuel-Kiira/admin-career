<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('ISO 3166-1 alpha-2 country code');
            $table->string('name', 100);
            $table->string('region', 50)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('frontend_url')->nullable()->comment('Full URL including protocol for the country-specific site');
            $table->string('domain')->nullable()->comment('Domain name without protocol');
            $table->string('currency_symbol', 5)->nullable();
            $table->decimal('default_lat', 10, 8)->nullable();
            $table->decimal('default_lng', 11, 8)->nullable();
            $table->string('flag', 5)->nullable();
            $table->string('capital', 100)->nullable();
            $table->decimal('capital_lat', 10, 8)->nullable();
            $table->decimal('capital_lng', 11, 8)->nullable();
            $table->string('phone_code', 10)->nullable();
            
            // ============================================================
            // SERVICE FEATURE FLAGS - Booleans to control frontend features
            // ============================================================
            
            // Job Seeker & Employer Services
            $table->boolean('can_view_casual_workers')->default(false);
            $table->boolean('can_view_blue_collar_workers')->default(false);
            $table->boolean('can_accept_cv_services')->default(false);
            $table->boolean('can_offer_exam_services')->default(false);
            $table->boolean('can_view_salary_insights')->default(false);
            $table->boolean('can_view_cost_of_living_tools')->default(false);
            $table->boolean('can_use_social_media_services')->default(false);
            $table->boolean('can_view_employer_services')->default(false);
            $table->boolean('can_view_jobseeker_services')->default(false);
            $table->boolean('can_access_subscription')->default(false);
            
            // Additional Traffic & Engagement Features
            $table->boolean('can_view_company_profiles')->default(false);
            $table->boolean('can_view_industry_insights')->default(false);
            $table->boolean('can_access_career_advice')->default(false);
            $table->boolean('can_view_job_alerts')->default(false);
            $table->boolean('can_use_resume_builder')->default(false);
            $table->boolean('can_view_employer_reviews')->default(false);
            $table->boolean('can_access_skill_assessment')->default(false);
            $table->boolean('can_view_market_trends')->default(false);
            $table->boolean('can_use_job_comparison_tools')->default(false);
            $table->boolean('can_access_networking_events')->default(false);
            $table->boolean('can_view_training_courses')->default(false);
            $table->boolean('can_use_chat_support')->default(false);
            
            // Premium/Paid Features
            $table->boolean('can_access_premium_content')->default(false);
            $table->boolean('can_view_verified_employers')->default(false);
            $table->boolean('can_use_priority_application')->default(false);
            $table->boolean('can_view_exclusive_jobs')->default(false);
            $table->boolean('can_access_interview_coaching')->default(false);
            $table->boolean('can_view_salary_negotiation_tips')->default(false);
            
            // Job Posting Features
            $table->boolean('can_post_jobs')->default(false);
            $table->boolean('can_post_featured_jobs')->default(false);
            $table->boolean('can_post_urgent_jobs')->default(false);
            $table->boolean('can_use_job_analytics')->default(false);
            $table->boolean('can_manage_applications')->default(false);
            
            // ============================================================
            // END SERVICE FEATURE FLAGS
            // ============================================================
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index('sort_order');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Insert default countries with features
        $this->seedDefaultCountries();
    }

    /**
     * Seed default countries with feature flags
     */
    private function seedDefaultCountries()
    {
        // Define default values for all feature flags
        $defaultFeatures = [
            'can_view_casual_workers' => false,
            'can_view_blue_collar_workers' => false,
            'can_accept_cv_services' => false,
            'can_offer_exam_services' => false,
            'can_view_salary_insights' => false,
            'can_view_cost_of_living_tools' => false,
            'can_use_social_media_services' => false,
            'can_view_employer_services' => false,
            'can_view_jobseeker_services' => false,
            'can_access_subscription' => false,
            'can_view_company_profiles' => false,
            'can_view_industry_insights' => false,
            'can_access_career_advice' => false,
            'can_view_job_alerts' => false,
            'can_use_resume_builder' => false,
            'can_view_employer_reviews' => false,
            'can_access_skill_assessment' => false,
            'can_view_market_trends' => false,
            'can_use_job_comparison_tools' => false,
            'can_access_networking_events' => false,
            'can_view_training_courses' => false,
            'can_use_chat_support' => false,
            'can_access_premium_content' => false,
            'can_view_verified_employers' => false,
            'can_use_priority_application' => false,
            'can_view_exclusive_jobs' => false,
            'can_access_interview_coaching' => false,
            'can_view_salary_negotiation_tips' => false,
            'can_post_jobs' => false,
            'can_post_featured_jobs' => false,
            'can_post_urgent_jobs' => false,
            'can_use_job_analytics' => false,
            'can_manage_applications' => false,
        ];

        $countries = [
            [
                'code' => 'AU',
                'name' => 'Australia',
                'region' => 'Oceania',
                'timezone' => 'Australia/Sydney',
                'currency' => 'AUD',
                'currency_symbol' => '$',
                'frontend_url' => 'https://www.greataustraliajobs.com',
                'domain' => 'greataustraliajobs.com',
                'default_lat' => -25.2744,
                'default_lng' => 133.7751,
                'flag' => '🇦🇺',
                'capital' => 'Canberra',
                'capital_lat' => -35.2809,
                'capital_lng' => 149.1300,
                'phone_code' => '+61',
                'sort_order' => 1,
            ],
            [
                'code' => 'UG',
                'name' => 'Uganda',
                'region' => 'East Africa',
                'timezone' => 'Africa/Kampala',
                'currency' => 'UGX',
                'currency_symbol' => 'UGX',
                'frontend_url' => 'https://www.greatugandajobs.com',
                'domain' => 'greatugandajobs.com',
                'default_lat' => 1.3733,
                'default_lng' => 32.2903,
                'flag' => '🇺🇬',
                'capital' => 'Kampala',
                'capital_lat' => 0.3476,
                'capital_lng' => 32.5825,
                'phone_code' => '+256',
                'sort_order' => 2,
            ],
            [
                'code' => 'KE',
                'name' => 'Kenya',
                'region' => 'East Africa',
                'timezone' => 'Africa/Nairobi',
                'currency' => 'KES',
                'currency_symbol' => 'KSh',
                'frontend_url' => 'https://www.greatkenyanjobs.com',
                'domain' => 'greatkenyanjobs.com',
                'default_lat' => -1.2921,
                'default_lng' => 36.8219,
                'flag' => '🇰🇪',
                'capital' => 'Nairobi',
                'capital_lat' => -1.2921,
                'capital_lng' => 36.8219,
                'phone_code' => '+254',
                'sort_order' => 3,
            ],
            [
                'code' => 'TZ',
                'name' => 'Tanzania',
                'region' => 'East Africa',
                'timezone' => 'Africa/Dar_es_Salaam',
                'currency' => 'TZS',
                'currency_symbol' => 'TSh',
                'frontend_url' => 'https://www.greattanzaniajobs.com',
                'domain' => 'greattanzaniajobs.com',
                'default_lat' => -6.7924,
                'default_lng' => 39.2083,
                'flag' => '🇹🇿',
                'capital' => 'Dodoma',
                'capital_lat' => -6.1620,
                'capital_lng' => 35.7516,
                'phone_code' => '+255',
                'sort_order' => 4,
            ],
            [
                'code' => 'RW',
                'name' => 'Rwanda',
                'region' => 'East Africa',
                'timezone' => 'Africa/Kigali',
                'currency' => 'RWF',
                'currency_symbol' => 'FRw',
                'frontend_url' => 'https://www.greatrwandajobs.com',
                'domain' => 'greatrwandajobs.com',
                'default_lat' => -1.9441,
                'default_lng' => 30.0619,
                'flag' => '🇷🇼',
                'capital' => 'Kigali',
                'capital_lat' => -1.9441,
                'capital_lng' => 30.0619,
                'phone_code' => '+250',
                'sort_order' => 5,
            ],
            [
                'code' => 'ZA',
                'name' => 'South Africa',
                'region' => 'Southern Africa',
                'timezone' => 'Africa/Johannesburg',
                'currency' => 'ZAR',
                'currency_symbol' => 'R',
                'frontend_url' => 'https://www.greatsouthafricajobs.com',
                'domain' => 'greatsouthafricajobs.com',
                'default_lat' => -30.5595,
                'default_lng' => 22.9375,
                'flag' => '🇿🇦',
                'capital' => 'Pretoria',
                'capital_lat' => -25.7479,
                'capital_lng' => 28.2293,
                'phone_code' => '+27',
                'sort_order' => 6,
            ],
            [
                'code' => 'MW',
                'name' => 'Malawi',
                'region' => 'South Africa',
                'timezone' => 'Africa/Blantyre',
                'currency' => 'MWK',
                'currency_symbol' => 'MK',
                'frontend_url' => 'https://www.greatmalawijobs.com',
                'domain' => 'greatmalawijobs.com',
                'default_lat' => -13.2543,
                'default_lng' => 34.3015,
                'flag' => '🇲🇼',
                'capital' => 'Lilongwe',
                'capital_lat' => -13.9626,
                'capital_lng' => 33.7741,
                'phone_code' => '+265',
                'sort_order' => 8,
            ],
            [
                'code' => 'SG',
                'name' => 'Singapore',
                'region' => 'Southeast Asia',
                'timezone' => 'Asia/Singapore',
                'currency' => 'SGD',
                'currency_symbol' => 'S$',
                'frontend_url' => 'https://www.greatsingaporejobs.com',
                'domain' => 'greatsingaporejobs.com',
                'default_lat' => 1.3521,
                'default_lng' => 103.8198,
                'flag' => '🇸🇬',
                'capital' => 'Singapore',
                'capital_lat' => 1.3521,
                'capital_lng' => 103.8198,
                'phone_code' => '+65',
                'sort_order' => 7,
            ],
        ];

        // Merge default features with each country
        $data = [];
        foreach ($countries as $country) {
            // Merge default features with country data
            $merged = array_merge($defaultFeatures, $country);
            
            // Override specific features for Australia
            if ($merged['code'] === 'AU') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = true;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = true;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = true;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = true;
                $merged['can_access_skill_assessment'] = true;
                $merged['can_view_market_trends'] = true;
                $merged['can_use_job_comparison_tools'] = true;
                $merged['can_access_networking_events'] = true;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = true;
                $merged['can_view_verified_employers'] = true;
                $merged['can_use_priority_application'] = true;
                $merged['can_view_exclusive_jobs'] = true;
                $merged['can_access_interview_coaching'] = true;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = true;
                $merged['can_post_urgent_jobs'] = true;
                $merged['can_use_job_analytics'] = true;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for Uganda
            if ($merged['code'] === 'UG') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = false;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = false;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = false;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = false;
                $merged['can_access_skill_assessment'] = false;
                $merged['can_view_market_trends'] = false;
                $merged['can_use_job_comparison_tools'] = false;
                $merged['can_access_networking_events'] = false;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = false;
                $merged['can_view_verified_employers'] = false;
                $merged['can_use_priority_application'] = false;
                $merged['can_view_exclusive_jobs'] = false;
                $merged['can_access_interview_coaching'] = false;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = false;
                $merged['can_post_urgent_jobs'] = false;
                $merged['can_use_job_analytics'] = false;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for Kenya
            if ($merged['code'] === 'KE') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = true;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = false;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = true;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = false;
                $merged['can_access_skill_assessment'] = true;
                $merged['can_view_market_trends'] = true;
                $merged['can_use_job_comparison_tools'] = false;
                $merged['can_access_networking_events'] = false;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = false;
                $merged['can_view_verified_employers'] = false;
                $merged['can_use_priority_application'] = false;
                $merged['can_view_exclusive_jobs'] = false;
                $merged['can_access_interview_coaching'] = false;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = true;
                $merged['can_post_urgent_jobs'] = false;
                $merged['can_use_job_analytics'] = false;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for Tanzania
            if ($merged['code'] === 'TZ') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = false;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = false;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = false;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = false;
                $merged['can_access_skill_assessment'] = false;
                $merged['can_view_market_trends'] = true;
                $merged['can_use_job_comparison_tools'] = false;
                $merged['can_access_networking_events'] = false;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = false;
                $merged['can_view_verified_employers'] = false;
                $merged['can_use_priority_application'] = false;
                $merged['can_view_exclusive_jobs'] = false;
                $merged['can_access_interview_coaching'] = false;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = false;
                $merged['can_post_urgent_jobs'] = false;
                $merged['can_use_job_analytics'] = false;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for Rwanda
            if ($merged['code'] === 'RW') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = false;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = false;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = false;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = false;
                $merged['can_access_skill_assessment'] = false;
                $merged['can_view_market_trends'] = false;
                $merged['can_use_job_comparison_tools'] = false;
                $merged['can_access_networking_events'] = false;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = false;
                $merged['can_view_verified_employers'] = false;
                $merged['can_use_priority_application'] = false;
                $merged['can_view_exclusive_jobs'] = false;
                $merged['can_access_interview_coaching'] = false;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = false;
                $merged['can_post_urgent_jobs'] = false;
                $merged['can_use_job_analytics'] = false;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for South Africa
            if ($merged['code'] === 'ZA') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = true;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = true;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = true;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = true;
                $merged['can_access_skill_assessment'] = true;
                $merged['can_view_market_trends'] = true;
                $merged['can_use_job_comparison_tools'] = true;
                $merged['can_access_networking_events'] = true;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = true;
                $merged['can_view_verified_employers'] = true;
                $merged['can_use_priority_application'] = true;
                $merged['can_view_exclusive_jobs'] = true;
                $merged['can_access_interview_coaching'] = true;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = true;
                $merged['can_post_urgent_jobs'] = true;
                $merged['can_use_job_analytics'] = true;
                $merged['can_manage_applications'] = true;
            }

            // Override specific features for Singapore
            if ($merged['code'] === 'SG') {
                $merged['can_view_casual_workers'] = true;
                $merged['can_view_blue_collar_workers'] = true;
                $merged['can_accept_cv_services'] = true;
                $merged['can_offer_exam_services'] = true;
                $merged['can_view_salary_insights'] = true;
                $merged['can_view_cost_of_living_tools'] = true;
                $merged['can_use_social_media_services'] = true;
                $merged['can_view_employer_services'] = true;
                $merged['can_view_jobseeker_services'] = true;
                $merged['can_access_subscription'] = true;
                $merged['can_view_company_profiles'] = true;
                $merged['can_view_industry_insights'] = true;
                $merged['can_access_career_advice'] = true;
                $merged['can_view_job_alerts'] = true;
                $merged['can_use_resume_builder'] = true;
                $merged['can_view_employer_reviews'] = true;
                $merged['can_access_skill_assessment'] = true;
                $merged['can_view_market_trends'] = true;
                $merged['can_use_job_comparison_tools'] = true;
                $merged['can_access_networking_events'] = true;
                $merged['can_view_training_courses'] = true;
                $merged['can_use_chat_support'] = true;
                $merged['can_access_premium_content'] = true;
                $merged['can_view_verified_employers'] = true;
                $merged['can_use_priority_application'] = true;
                $merged['can_view_exclusive_jobs'] = true;
                $merged['can_access_interview_coaching'] = true;
                $merged['can_view_salary_negotiation_tips'] = true;
                $merged['can_post_jobs'] = true;
                $merged['can_post_featured_jobs'] = true;
                $merged['can_post_urgent_jobs'] = true;
                $merged['can_use_job_analytics'] = true;
                $merged['can_manage_applications'] = true;
            }

            $data[] = $merged;
        }

        DB::table('countries')->insert($data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};