<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentSource;

class PaymentSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            // Recruitment company specific sources
            ['name' => 'Job Advertisers', 'slug' => 'job_advertisers', 'icon' => 'ki-duotone ki-briefcase', 'color' => 'primary', 'category' => 'revenue'],
            ['name' => 'Google AdSense', 'slug' => 'google_adsense', 'icon' => 'ki-duotone ki-google', 'color' => 'warning', 'category' => 'revenue'],
            ['name' => 'Maids Business', 'slug' => 'maids_business', 'icon' => 'ki-duotone ki-home', 'color' => 'info', 'category' => 'revenue'],
            ['name' => 'Casual Work Management', 'slug' => 'casual_work_mgt', 'icon' => 'ki-duotone ki-briefcase', 'color' => 'success', 'category' => 'revenue'],
            ['name' => 'Workforce Management', 'slug' => 'workforce_mgt', 'icon' => 'ki-duotone ki-users', 'color' => 'primary', 'category' => 'revenue'],
            ['name' => 'Recruitment', 'slug' => 'recruitment', 'icon' => 'ki-duotone ki-user-search', 'color' => 'success', 'category' => 'revenue'],
            ['name' => 'Consultancy', 'slug' => 'consultancy', 'icon' => 'ki-duotone ki-chart', 'color' => 'info', 'category' => 'revenue'],
            ['name' => 'CV Shortlisting', 'slug' => 'cv_shortlisting', 'icon' => 'ki-duotone ki-document', 'color' => 'warning', 'category' => 'revenue'],
            ['name' => 'Interview Scheduling', 'slug' => 'interview_scheduling', 'icon' => 'ki-duotone ki-calendar', 'color' => 'primary', 'category' => 'revenue'],
            ['name' => 'Background Checks', 'slug' => 'background_checks', 'icon' => 'ki-duotone ki-shield', 'color' => 'danger', 'category' => 'revenue'],
            ['name' => 'Training Services', 'slug' => 'training_services', 'icon' => 'ki-duotone ki-book', 'color' => 'info', 'category' => 'revenue'],
            ['name' => 'Staff Outsourcing', 'slug' => 'staff_outsourcing', 'icon' => 'ki-duotone ki-user', 'color' => 'success', 'category' => 'revenue'],
            ['name' => 'Payroll Services', 'slug' => 'payroll_services', 'icon' => 'ki-duotone ki-wallet', 'color' => 'warning', 'category' => 'revenue'],
            ['name' => 'HR Consulting', 'slug' => 'hr_consulting', 'icon' => 'ki-duotone ki-chart', 'color' => 'primary', 'category' => 'revenue'],
            
            // General sources
            ['name' => 'Client Payment', 'slug' => 'client_payment', 'icon' => 'ki-duotone ki-building', 'color' => 'primary', 'category' => 'revenue'],
            ['name' => 'Customer Payment', 'slug' => 'customer_payment', 'icon' => 'ki-duotone ki-user', 'color' => 'success', 'category' => 'revenue'],
            ['name' => 'Investor', 'slug' => 'investor', 'icon' => 'ki-duotone ki-chart-line', 'color' => 'info', 'category' => 'capital'],
            ['name' => 'Bank Loan', 'slug' => 'bank_loan', 'icon' => 'ki-duotone ki-building', 'color' => 'warning', 'category' => 'loan'],
            ['name' => 'Shareholder', 'slug' => 'shareholder', 'icon' => 'ki-duotone ki-users', 'color' => 'danger', 'category' => 'capital'],
            ['name' => 'Grant', 'slug' => 'grant', 'icon' => 'ki-duotone ki-gift', 'color' => 'success', 'category' => 'revenue'],
            ['name' => 'Donation', 'slug' => 'donation', 'icon' => 'ki-duotone ki-heart', 'color' => 'danger', 'category' => 'revenue'],
            ['name' => 'Other', 'slug' => 'other', 'icon' => 'ki-duotone ki-category', 'color' => 'secondary', 'category' => 'other'],
        ];

        foreach ($sources as $index => $source) {
            PaymentSource::updateOrCreate(
                ['slug' => $source['slug']],
                array_merge($source, ['sort_order' => $index + 1])
            );
        }
    }
}