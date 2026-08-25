<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\PaymentSource;
use App\Models\PaymentPurpose;
use App\Models\Department;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Str;

class DepositSeeder extends Seeder
{
    public function run(): void
    {
        // Get payment methods
        $mtnMethod = PaymentMethod::where('code', 'MTN_UGX')->first();
        $airtelMethod = PaymentMethod::where('code', 'AIRTEL_UGX')->first();
        $bankMethod = PaymentMethod::where('code', 'STANBIC_UGX')->first();
        $paypalMethod = PaymentMethod::where('code', 'PAYPAL_USD')->first();
        $cashMethod = PaymentMethod::where('code', 'CASH_HO')->first();
        
        // Get currencies
        $ugx = Currency::where('code', 'UGX')->first();
        $usd = Currency::where('code', 'USD')->first();
        
        // Get departments
        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $opsDept = Department::where('code', 'OPS')->first();
        $bdDept = Department::where('code', 'BD')->first();
        $maidDept = Department::where('code', 'MAID')->first();
        $jobDept = Department::where('code', 'JOB')->first();
        $cwmDept = Department::where('code', 'CWM')->first();
        $wfmDept = Department::where('code', 'WFM')->first();
        $finDept = Department::where('code', 'FIN')->first();
        $recDept = Department::where('code', 'REC')->first();
        $consDept = Department::where('code', 'CONS')->first();
        $cvsDept = Department::where('code', 'CVS')->first();
        
        // Get users (depositors)
        $samuel = User::where('email', 'samuelkiiraeluk@gmail.com')->first();
        $martin = User::where('email', 'mubmart7@gmail.com')->first();
        $superAdmin = User::where('email', 'superadmin@lafab.com')->first();
        $john = User::where('email', 'john.doe@lafab.com')->first();
        $jane = User::where('email', 'jane.smith@lafab.com')->first();
        $michael = User::where('email', 'michael.johnson@lafab.com')->first();
        $sarah = User::where('email', 'sarah.williams@lafab.com')->first();
        
        // Get sources
        $jobAdvertisersSource = PaymentSource::where('slug', 'job_advertisers')->first();
        $googleAdsenseSource = PaymentSource::where('slug', 'google_adsense')->first();
        $recruitmentSource = PaymentSource::where('slug', 'recruitment')->first();
        $consultancySource = PaymentSource::where('slug', 'consultancy')->first();
        $cvShortlistingSource = PaymentSource::where('slug', 'cv_shortlisting')->first();
        $maidsBusinessSource = PaymentSource::where('slug', 'maids_business')->first();
        $casualWorkSource = PaymentSource::where('slug', 'casual_work_mgt')->first();
        $workforceSource = PaymentSource::where('slug', 'workforce_mgt')->first();
        
        // Get purposes
        $jobPostingPurpose = PaymentPurpose::where('slug', 'job_posting_fee')->first();
        $serviceFeePurpose = PaymentPurpose::where('slug', 'service_fee')->first();
        $recruitmentFeePurpose = PaymentPurpose::where('slug', 'recruitment_fee')->first();
        $consultancyFeePurpose = PaymentPurpose::where('slug', 'consultancy_fee')->first();
        $adsenseRevenuePurpose = PaymentPurpose::where('slug', 'adsense_revenue')->first();
        $trainingFeePurpose = PaymentPurpose::where('slug', 'training_fee')->first();
        $staffOutsourcingPurpose = PaymentPurpose::where('slug', 'staff_outsourcing_fee')->first();
        
        // Get admin user for created_by
        $adminUser = User::where('email', 'superadmin@lafab.com')->first() ?? User::first();
        
        if (!$adminUser) {
            $this->command->error('No admin user found.');
            return;
        }

        $paymentService = app(PaymentService::class);

        $deposits = [];

        // Deposit 1: Job Advertiser payment via MTN Mobile Money - Job Department
        if ($mtnMethod && $jobAdvertisersSource && $jobPostingPurpose && $jobDept && $john) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $mtnMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 2500000,
                'fee' => 12500,
                'net_amount' => 2487500,
                'deposit_method' => 'mobile_money',
                'reference_number' => 'MTN' . rand(100000, 999999),
                'department_id' => $jobDept->id,
                'depositor_id' => $john->id,
                'source_id' => $jobAdvertisersSource->id,
                'source_reference' => 'JOB_AD_001',
                'invoice_number' => 'INV-JOB-2024-001',
                'purpose_id' => $jobPostingPurpose->id,
                'purpose_description' => 'Premium job posting package for 20 positions',
                'status' => 'completed',
                'deposit_date' => now()->subDays(2),
                'cleared_date' => now()->subDays(2),
                'depositor_name' => 'ABC Company Ltd',
                'depositor_phone' => '+256712345678',
                'depositor_email' => 'accounts@abccompany.com',
                'description' => 'Payment for job postings',
                'notes' => 'Premium package - 20 job slots',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 2: Google AdSense payment via Bank Transfer - Business Development Department
        if ($bankMethod && $googleAdsenseSource && $adsenseRevenuePurpose && $bdDept && $jane) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $bankMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 10000000,
                'fee' => 0,
                'net_amount' => 10000000,
                'deposit_method' => 'bank_transfer',
                'reference_number' => 'ADS' . rand(100000, 999999),
                'department_id' => $bdDept->id,
                'depositor_id' => $jane->id,
                'source_id' => $googleAdsenseSource->id,
                'source_reference' => 'ADSENSE_Q2_2024',
                'purpose_id' => $adsenseRevenuePurpose->id,
                'purpose_description' => 'Google AdSense revenue for Q2 2024',
                'status' => 'completed',
                'deposit_date' => now()->subDays(5),
                'cleared_date' => now()->subDays(4),
                'depositor_name' => 'Google Inc',
                'depositor_email' => 'adsense@google.com',
                'description' => 'AdSense revenue payment',
                'notes' => 'Monthly AdSense earnings',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 3: Recruitment fee via Bank Transfer - Recruitment Department
        if ($bankMethod && $recruitmentSource && $recruitmentFeePurpose && $recDept && $sarah) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $bankMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 5000000,
                'fee' => 50000,
                'net_amount' => 4950000,
                'deposit_method' => 'bank_transfer',
                'reference_number' => 'REC' . rand(100000, 999999),
                'department_id' => $recDept->id,
                'depositor_id' => $sarah->id,
                'source_id' => $recruitmentSource->id,
                'source_reference' => 'REC_2024_001',
                'customer_id' => 'CUST-001',
                'invoice_number' => 'INV-REC-2024-001',
                'purpose_id' => $recruitmentFeePurpose->id,
                'purpose_description' => 'Recruitment fee for Senior Developer position',
                'status' => 'completed',
                'deposit_date' => now()->subDays(3),
                'cleared_date' => now()->subDays(3),
                'depositor_name' => 'Tech Company Ltd',
                'depositor_phone' => '+256734567890',
                'depositor_email' => 'hr@techcompany.com',
                'description' => 'Recruitment service fee',
                'notes' => 'Senior Developer placement',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 4: Consultancy fee via PayPal - Consultancy Department
        if ($paypalMethod && $consultancySource && $consultancyFeePurpose && $consDept && $michael) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $paypalMethod->id,
                'currency_id' => $usd->id,
                'amount' => 250000,
                'fee' => 8500,
                'net_amount' => 241500,
                'deposit_method' => 'e_wallet',
                'reference_number' => 'PP' . rand(100000, 999999),
                'department_id' => $consDept->id,
                'depositor_id' => $michael->id,
                'source_id' => $consultancySource->id,
                'source_reference' => 'CONS_2024_001',
                'contract_number' => 'CON-2024-001',
                'purpose_id' => $consultancyFeePurpose->id,
                'purpose_description' => 'HR consultancy services for Q2 2024',
                'status' => 'completed',
                'deposit_date' => now()->subDays(4),
                'cleared_date' => now()->subDays(4),
                'depositor_name' => 'Global Consulting Inc',
                'depositor_email' => 'payments@globalconsulting.com',
                'description' => 'Consultancy fee payment',
                'notes' => 'Quarterly retainer fee',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 5: CV Shortlisting fee via Cash - CV Shortlisting Department
        if ($cashMethod && $cvShortlistingSource && $serviceFeePurpose && $cvsDept && $samuel) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $cashMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 500000,
                'fee' => 0,
                'net_amount' => 500000,
                'deposit_method' => 'cash',
                'reference_number' => 'CASH' . rand(100000, 999999),
                'department_id' => $cvsDept->id,
                'depositor_id' => $samuel->id,
                'source_id' => $cvShortlistingSource->id,
                'source_reference' => 'CV_2024_001',
                'invoice_number' => 'INV-CVS-2024-001',
                'purpose_id' => $serviceFeePurpose->id,
                'purpose_description' => 'CV shortlisting service for 50 candidates',
                'status' => 'completed',
                'deposit_date' => now()->subDay(),
                'cleared_date' => now()->subDay(),
                'depositor_name' => 'Walk-in Client',
                'depositor_phone' => '+256745678901',
                'description' => 'Cash payment for CV shortlisting',
                'notes' => 'Bulk CV shortlisting',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 6: Maids Business via Airtel Mobile Money - Maids Department
        if ($airtelMethod && $maidsBusinessSource && $serviceFeePurpose && $maidDept && $martin) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $airtelMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 1200000,
                'fee' => 6000,
                'net_amount' => 1194000,
                'deposit_method' => 'mobile_money',
                'reference_number' => 'AIRTEL' . rand(100000, 999999),
                'department_id' => $maidDept->id,
                'depositor_id' => $martin->id,
                'source_id' => $maidsBusinessSource->id,
                'source_reference' => 'MAID_2024_001',
                'customer_id' => 'CUST-005',
                'invoice_number' => 'INV-MAID-2024-001',
                'purpose_id' => $serviceFeePurpose->id,
                'purpose_description' => 'Maid placement fee for 3 domestic workers',
                'status' => 'completed',
                'deposit_date' => now()->subDays(6),
                'cleared_date' => now()->subDays(6),
                'depositor_name' => 'Home Care Ltd',
                'depositor_phone' => '+256756789012',
                'depositor_email' => 'info@homecare.com',
                'description' => 'Maid placement service fee',
                'notes' => '3 maids placed',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 7: Casual Work Management via MTN Mobile Money - CWM Department
        if ($mtnMethod && $casualWorkSource && $serviceFeePurpose && $cwmDept && $john) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $mtnMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 750000,
                'fee' => 3750,
                'net_amount' => 746250,
                'deposit_method' => 'mobile_money',
                'reference_number' => 'CWM' . rand(100000, 999999),
                'department_id' => $cwmDept->id,
                'depositor_id' => $john->id,
                'source_id' => $casualWorkSource->id,
                'source_reference' => 'CWM_2024_001',
                'customer_id' => 'CUST-008',
                'invoice_number' => 'INV-CWM-2024-001',
                'purpose_id' => $staffOutsourcingPurpose->id,
                'purpose_description' => 'Casual labor management for construction project',
                'status' => 'completed',
                'deposit_date' => now()->subDays(8),
                'cleared_date' => now()->subDays(7),
                'depositor_name' => 'BuildRight Construction',
                'depositor_phone' => '+256767890123',
                'depositor_email' => 'accounts@buildright.com',
                'description' => 'Casual worker management fee',
                'notes' => 'Monthly retainer',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 8: Workforce Management via Bank Transfer - WFM Department
        if ($bankMethod && $workforceSource && $staffOutsourcingPurpose && $wfmDept && $jane) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $bankMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 3000000,
                'fee' => 15000,
                'net_amount' => 2985000,
                'deposit_method' => 'bank_transfer',
                'reference_number' => 'WFM' . rand(100000, 999999),
                'department_id' => $wfmDept->id,
                'depositor_id' => $jane->id,
                'source_id' => $workforceSource->id,
                'source_reference' => 'WFM_2024_001',
                'contract_number' => 'CON-WFM-2024-001',
                'invoice_number' => 'INV-WFM-2024-001',
                'purpose_id' => $staffOutsourcingPurpose->id,
                'purpose_description' => 'Workforce optimization and scheduling services',
                'status' => 'completed',
                'deposit_date' => now()->subDays(10),
                'cleared_date' => now()->subDays(9),
                'depositor_name' => 'Manufacturing Corp',
                'depositor_phone' => '+256778901234',
                'depositor_email' => 'finance@manufacturingcorp.com',
                'description' => 'Workforce management services',
                'notes' => 'Quarterly subscription',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 9: Training Services via Bank Transfer - HR Department
        if ($bankMethod && $hrDept && $trainingFeePurpose && $sarah) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $bankMethod->id,
                'currency_id' => $ugx->id,
                'amount' => 2000000,
                'fee' => 10000,
                'net_amount' => 1990000,
                'deposit_method' => 'bank_transfer',
                'reference_number' => 'TRN' . rand(100000, 999999),
                'department_id' => $hrDept->id,
                'depositor_id' => $sarah->id,
                'source_id' => $recruitmentSource->id,
                'source_reference' => 'TRN_2024_001',
                'customer_id' => 'CUST-010',
                'invoice_number' => 'INV-HR-2024-001',
                'purpose_id' => $trainingFeePurpose->id,
                'purpose_description' => 'Leadership training program for 20 managers',
                'status' => 'completed',
                'deposit_date' => now()->subDays(12),
                'cleared_date' => now()->subDays(11),
                'depositor_name' => 'Corporate Training Ltd',
                'depositor_phone' => '+256789012345',
                'depositor_email' => 'training@corporatetraining.com',
                'description' => 'Corporate training services',
                'notes' => '2-day workshop',
                'created_by' => $adminUser->id,
            ];
        }

        // Deposit 10: IT Consultancy via PayPal - IT Department
        if ($paypalMethod && $consultancySource && $consultancyFeePurpose && $itDept && $michael) {
            $deposits[] = [
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $paypalMethod->id,
                'currency_id' => $usd->id,
                'amount' => 500000,
                'fee' => 17000,
                'net_amount' => 483000,
                'deposit_method' => 'e_wallet',
                'reference_number' => 'ITCONS' . rand(100000, 999999),
                'department_id' => $itDept->id,
                'depositor_id' => $michael->id,
                'source_id' => $consultancySource->id,
                'source_reference' => 'IT_CONS_2024_001',
                'contract_number' => 'CON-IT-2024-001',
                'purpose_id' => $consultancyFeePurpose->id,
                'purpose_description' => 'IT systems audit and optimization',
                'status' => 'completed',
                'deposit_date' => now()->subDays(15),
                'cleared_date' => now()->subDays(14),
                'depositor_name' => 'Tech Solutions International',
                'depositor_email' => 'payments@techsolutions.com',
                'description' => 'IT consultancy services',
                'notes' => 'System audit project',
                'created_by' => $adminUser->id,
            ];
        }

        foreach ($deposits as $depositData) {
            try {
                $deposit = Deposit::create($depositData);
                
                // Process the deposit payment to increase balance
                $currency = $deposit->currency;
                $amountInDisplay = $currency->fromCents($deposit->net_amount);
                
                $transaction = $paymentService->deposit([
                    'payment_method_id' => $deposit->payment_method_id,
                    'amount' => $amountInDisplay,
                    'currency_id' => $deposit->currency_id,
                    'user_id' => $deposit->created_by,
                    'description' => $deposit->description,
                    'reference_table' => 'deposits',
                    'reference_id' => $deposit->id,
                    'external_reference' => $deposit->reference_number,
                    'metadata' => [
                        'deposit_method' => $deposit->deposit_method,
                        'source' => $deposit->source->name ?? 'Unknown',
                        'purpose' => $deposit->purpose->name ?? 'Unknown',
                        'department' => $deposit->department?->name,
                        'depositor' => $deposit->depositor?->name,
                    ],
                ]);
                
                $deposit->status = 'completed';
                $deposit->approved_by = $adminUser->id;
                $deposit->approved_at = now();
                $deposit->cleared_date = now();
                $deposit->save();
                
                $deptName = $deposit->department?->name ?? 'No Department';
                $depositorName = $deposit->depositor?->name ?? 'Unknown';
                $this->command->info("✓ Deposit #{$deposit->id}: {$deposit->source->name} - " . number_format($amountInDisplay, 2) . " " . $currency->code . " | Dept: {$deptName} | By: {$depositorName}");
                
            } catch (\Exception $e) {
                $this->command->error("✗ Failed: " . $e->getMessage());
            }
        }
        
        $this->command->newLine();
        $this->command->info('✅ Deposits seeded successfully!');
        
        // Display summary
        $this->command->newLine();
        $this->command->info('=== 📊 DEPOSIT SUMMARY ===');
        
        $departments = Department::all();
        foreach ($departments as $dept) {
            $total = Deposit::where('department_id', $dept->id)->where('status', 'completed')->sum('net_amount');
            $count = Deposit::where('department_id', $dept->id)->where('status', 'completed')->count();
            if ($total > 0) {
                $currency = Currency::where('code', 'UGX')->first();
                $this->command->line("  • {$dept->name}: {$currency->symbol} " . number_format($currency->fromCents($total), 0) . " ({$count} deposits)");
            }
        }
        
        // Display updated balances
        $this->command->newLine();
        $this->command->info('=== 📊 UPDATED BALANCES ===');
        
        $paymentMethods = PaymentMethod::with('currency')->get();
        foreach ($paymentMethods as $method) {
            $balance = $method->current_balance;
            $currency = $method->currency;
            $this->command->line("  • {$method->name}: " . ($currency ? $currency->formatAmount($balance) : '$' . number_format($balance / 100, 2)));
        }
    }
}