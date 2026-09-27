<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Helper: does the column already exist? ──────────────────
        $has = fn (string $col) => Schema::hasColumn('employer_profiles', $col);

        // ── Helper: does the index already exist? ───────────────────
        $hasIndex = function (string $indexName) {
            $db = DB::getDatabaseName();
            $row = DB::selectOne(
                "SELECT 1 FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?
                 LIMIT 1",
                [$db, 'employer_profiles', $indexName]
            );
            return (bool) $row;
        };

        Schema::table('employer_profiles', function (Blueprint $table) use ($has, $hasIndex) {

            // ── Identity / registration ─────────────────────────────
            if (!$has('legal_name'))          $table->string('legal_name')->nullable()->after('company_name');
            if (!$has('trading_name'))        $table->string('trading_name')->nullable()->after('legal_name');
            if (!$has('registration_number')) $table->string('registration_number')->nullable()->after('trading_name');

            // ── Company info ────────────────────────────────────────
            if (!$has('year_founded'))   $table->year('year_founded')->nullable()->after('company_description');
            if (!$has('company_email'))  $table->string('company_email')->nullable()->after('year_founded');
            if (!$has('company_phone'))  $table->string('company_phone', 30)->nullable()->after('company_email');
            if (!$has('linkedin_url'))   $table->string('linkedin_url')->nullable()->after('company_phone');
            if (!$has('facebook_url'))   $table->string('facebook_url')->nullable()->after('linkedin_url');
            if (!$has('twitter_url'))    $table->string('twitter_url')->nullable()->after('facebook_url');

            // ── Compliance / Tax ────────────────────────────────────
            if (!$has('tin_number'))                 $table->string('tin_number')->nullable()->after('twitter_url');
            if (!$has('nssf_number'))                $table->string('nssf_number')->nullable()->after('tin_number');
            if (!$has('ura_number'))                 $table->string('ura_number')->nullable()->after('nssf_number');
            if (!$has('vat_number'))                 $table->string('vat_number')->nullable()->after('ura_number');
            if (!$has('business_license_number'))    $table->string('business_license_number')->nullable()->after('vat_number');
            if (!$has('professional_license_number'))$table->string('professional_license_number')->nullable()->after('business_license_number');

            // ── Compliance document paths ───────────────────────────
            if (!$has('cert_incorporation_path'))       $table->string('cert_incorporation_path')->nullable()->after('professional_license_number');
            if (!$has('cert_incorporation_name'))       $table->string('cert_incorporation_name')->nullable()->after('cert_incorporation_path');
            if (!$has('cert_incorporation_expires_at')) $table->date('cert_incorporation_expires_at')->nullable()->after('cert_incorporation_name');

            if (!$has('trading_license_path'))       $table->string('trading_license_path')->nullable()->after('cert_incorporation_expires_at');
            if (!$has('trading_license_name'))       $table->string('trading_license_name')->nullable()->after('trading_license_path');
            if (!$has('trading_license_expires_at')) $table->date('trading_license_expires_at')->nullable()->after('trading_license_name');

            if (!$has('tin_certificate_path')) $table->string('tin_certificate_path')->nullable()->after('trading_license_expires_at');
            if (!$has('tin_certificate_name')) $table->string('tin_certificate_name')->nullable()->after('tin_certificate_path');

            if (!$has('nssf_certificate_path')) $table->string('nssf_certificate_path')->nullable()->after('tin_certificate_name');
            if (!$has('nssf_certificate_name')) $table->string('nssf_certificate_name')->nullable()->after('nssf_certificate_path');

            if (!$has('tax_clearance_path'))       $table->string('tax_clearance_path')->nullable()->after('nssf_certificate_name');
            if (!$has('tax_clearance_name'))       $table->string('tax_clearance_name')->nullable()->after('tax_clearance_path');
            if (!$has('tax_clearance_expires_at')) $table->date('tax_clearance_expires_at')->nullable()->after('tax_clearance_name');

            if (!$has('insurance_path'))       $table->string('insurance_path')->nullable()->after('tax_clearance_expires_at');
            if (!$has('insurance_name'))       $table->string('insurance_name')->nullable()->after('insurance_path');
            if (!$has('insurance_expires_at')) $table->date('insurance_expires_at')->nullable()->after('insurance_name');

            if (!$has('professional_license_path'))       $table->string('professional_license_path')->nullable()->after('insurance_expires_at');
            if (!$has('professional_license_name'))       $table->string('professional_license_name')->nullable()->after('professional_license_path');
            if (!$has('professional_license_expires_at')) $table->date('professional_license_expires_at')->nullable()->after('professional_license_name');

            if (!$has('extra_documents')) $table->json('extra_documents')->nullable()->after('professional_license_expires_at');

            // ── Compliance status ───────────────────────────────────
            if (!$has('compliance_status')) {
                $table->enum('compliance_status', ['incomplete', 'submitted', 'verified', 'rejected'])
                    ->default('incomplete')
                    ->after('extra_documents');
            }
            if (!$has('compliance_submitted_at')) $table->timestamp('compliance_submitted_at')->nullable()->after('compliance_status');
            if (!$has('compliance_verified_at'))  $table->timestamp('compliance_verified_at')->nullable()->after('compliance_submitted_at');

            if (!$has('compliance_verified_by')) {
                $table->foreignId('compliance_verified_by')->nullable()->after('compliance_verified_at')
                    ->constrained('users')->nullOnDelete();
            }

            if (!$has('compliance_notes')) $table->text('compliance_notes')->nullable()->after('compliance_verified_by');

            // ── Onboarding ──────────────────────────────────────────
            if (!$has('onboarding_complete'))       $table->boolean('onboarding_complete')->default(false)->after('compliance_notes');
            if (!$has('onboarding_completed_at'))   $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_complete');

            // ── Indexes — only if missing ───────────────────────────
            if (!$hasIndex('employer_profiles_compliance_status_index')) {
                $table->index('compliance_status');
            }
            if (!$hasIndex('employer_profiles_onboarding_complete_index')) {
                $table->index('onboarding_complete');
            }
            // ⚠️ is_verified index already exists from the original migration — skip it
            // if (!$hasIndex('employer_profiles_is_verified_index')) {
            //     $table->index('is_verified');
            // }
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {

            // Drop indexes only if they exist
            $indexExists = function (string $indexName) {
                $db = DB::getDatabaseName();
                return (bool) DB::selectOne(
                    "SELECT 1 FROM information_schema.statistics
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?
                     LIMIT 1",
                    [$db, 'employer_profiles', $indexName]
                );
            };

            if ($indexExists('employer_profiles_compliance_status_index')) {
                $table->dropIndex('employer_profiles_compliance_status_index');
            }
            if ($indexExists('employer_profiles_onboarding_complete_index')) {
                $table->dropIndex('employer_profiles_onboarding_complete_index');
            }

            // Drop foreign key only if the column exists
            if (Schema::hasColumn('employer_profiles', 'compliance_verified_by')) {
                $table->dropForeign(['compliance_verified_by']);
            }

            // Drop columns — only if they exist
            $columns = [
                'legal_name', 'trading_name', 'registration_number',
                'year_founded', 'company_email', 'company_phone',
                'linkedin_url', 'facebook_url', 'twitter_url',
                'tin_number', 'nssf_number', 'ura_number', 'vat_number',
                'business_license_number', 'professional_license_number',
                'cert_incorporation_path', 'cert_incorporation_name', 'cert_incorporation_expires_at',
                'trading_license_path', 'trading_license_name', 'trading_license_expires_at',
                'tin_certificate_path', 'tin_certificate_name',
                'nssf_certificate_path', 'nssf_certificate_name',
                'tax_clearance_path', 'tax_clearance_name', 'tax_clearance_expires_at',
                'insurance_path', 'insurance_name', 'insurance_expires_at',
                'professional_license_path', 'professional_license_name', 'professional_license_expires_at',
                'extra_documents',
                'compliance_status', 'compliance_submitted_at', 'compliance_verified_at',
                'compliance_verified_by', 'compliance_notes',
                'onboarding_complete', 'onboarding_completed_at',
            ];

            $existing = array_filter($columns, fn ($c) => Schema::hasColumn('employer_profiles', $c));
            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};