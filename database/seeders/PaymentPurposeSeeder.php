<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentPurpose;

class PaymentPurposeSeeder extends Seeder
{
    public function run(): void
    {
        $purposes = [
            ['name' => 'Job Posting Fee', 'slug' => 'job_posting_fee', 'icon' => 'ki-duotone ki-briefcase', 'color' => 'primary', 'category' => 'income'],
            ['name' => 'Subscription', 'slug' => 'subscription', 'icon' => 'ki-duotone ki-calendar', 'color' => 'info', 'category' => 'income'],
            ['name' => 'Service Fee', 'slug' => 'service_fee', 'icon' => 'ki-duotone ki-chart', 'color' => 'success', 'category' => 'income'],
            ['name' => 'Consultancy Fee', 'slug' => 'consultancy_fee', 'icon' => 'ki-duotone ki-chart', 'color' => 'warning', 'category' => 'income'],
            ['name' => 'Recruitment Fee', 'slug' => 'recruitment_fee', 'icon' => 'ki-duotone ki-user-search', 'color' => 'primary', 'category' => 'income'],
            ['name' => 'CV Shortlisting Fee', 'slug' => 'cv_shortlisting_fee', 'icon' => 'ki-duotone ki-document', 'color' => 'success', 'category' => 'income'],
            ['name' => 'Training Fee', 'slug' => 'training_fee', 'icon' => 'ki-duotone ki-book', 'color' => 'info', 'category' => 'income'],
            ['name' => 'Background Check Fee', 'slug' => 'background_check_fee', 'icon' => 'ki-duotone ki-shield', 'color' => 'danger', 'category' => 'income'],
            ['name' => 'Interview Scheduling Fee', 'slug' => 'interview_scheduling_fee', 'icon' => 'ki-duotone ki-calendar', 'color' => 'warning', 'category' => 'income'],
            ['name' => 'Staff Outsourcing Fee', 'slug' => 'staff_outsourcing_fee', 'icon' => 'ki-duotone ki-user', 'color' => 'primary', 'category' => 'income'],
            ['name' => 'Payroll Processing Fee', 'slug' => 'payroll_processing_fee', 'icon' => 'ki-duotone ki-wallet', 'color' => 'info', 'category' => 'income'],
            ['name' => 'AdSense Revenue', 'slug' => 'adsense_revenue', 'icon' => 'ki-duotone ki-google', 'color' => 'warning', 'category' => 'income'],
            ['name' => 'Sales Revenue', 'slug' => 'sales_revenue', 'icon' => 'ki-duotone ki-chart-line', 'color' => 'success', 'category' => 'income'],
            ['name' => 'Capital Injection', 'slug' => 'capital_injection', 'icon' => 'ki-duotone ki-chart-line', 'color' => 'danger', 'category' => 'income'],
            ['name' => 'Loan Disbursement', 'slug' => 'loan_disbursement', 'icon' => 'ki-duotone ki-building', 'color' => 'warning', 'category' => 'income'],
            ['name' => 'Refund', 'slug' => 'refund', 'icon' => 'ki-duotone ki-undo', 'color' => 'info', 'category' => 'income'],
            ['name' => 'Other Income', 'slug' => 'other_income', 'icon' => 'ki-duotone ki-category', 'color' => 'secondary', 'category' => 'income'],
        ];

        foreach ($purposes as $index => $purpose) {
            PaymentPurpose::updateOrCreate(
                ['slug' => $purpose['slug']],
                array_merge($purpose, ['sort_order' => $index + 1])
            );
        }
    }
}