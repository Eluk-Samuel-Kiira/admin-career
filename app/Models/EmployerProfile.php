<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\Job\{ JobPost };

class EmployerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employer_profiles';

    // =================================================================
    // Mass assignment
    // =================================================================
    protected $fillable = [
        // Identity
        'user_id',
        'company_id', 
        'company_name',
        'legal_name',
        'trading_name',
        'registration_number',
        'company_logo',
        'company_website',
        'company_description',
        'company_size',
        'year_founded',
        'company_email',
        'company_phone',
        'linkedin_url',
        'facebook_url',
        'twitter_url',
        'industry',

        // Contact person
        'contact_name',
        'contact_position',
        'contact_phone',
        'contact_email',

        // Address
        'address',
        'city',
        'state',
        'country_code',
        'postal_code',

        // Tax / compliance numbers
        'tin_number',
        'nssf_number',
        'ura_number',
        'vat_number',
        'business_license_number',
        'professional_license_number',

        // Compliance document paths
        'cert_incorporation_path',
        'cert_incorporation_name',
        'cert_incorporation_expires_at',
        'trading_license_path',
        'trading_license_name',
        'trading_license_expires_at',
        'tin_certificate_path',
        'tin_certificate_name',
        'nssf_certificate_path',
        'nssf_certificate_name',
        'tax_clearance_path',
        'tax_clearance_name',
        'tax_clearance_expires_at',
        'insurance_path',
        'insurance_name',
        'insurance_expires_at',
        'professional_license_path',
        'professional_license_name',
        'professional_license_expires_at',
        'extra_documents',

        // Compliance status
        'compliance_status',
        'compliance_submitted_at',
        'compliance_verified_at',
        'compliance_verified_by',
        'compliance_notes',

        // Onboarding
        'onboarding_complete',
        'onboarding_completed_at',

        // Status flags
        'is_verified',
        'is_active',
        'verification_status',

        // Subscription / billing
        'subscription_plan',
        'subscription_expires_at',
        'job_postings_remaining',
        'featured_until',
    ];

    // =================================================================
    // Casts
    // =================================================================
    protected $casts = [
        'is_verified'                => 'boolean',
        'is_active'                  => 'boolean',
        'onboarding_complete'        => 'boolean',
        'job_postings_remaining'     => 'integer',
        'year_founded'               => 'integer',
        'subscription_expires_at'    => 'datetime',
        'featured_until'             => 'datetime',
        'compliance_submitted_at'    => 'datetime',
        'compliance_verified_at'     => 'datetime',
        'onboarding_completed_at'    => 'datetime',
        'cert_incorporation_expires_at'  => 'date',
        'trading_license_expires_at'     => 'date',
        'tax_clearance_expires_at'       => 'date',
        'insurance_expires_at'           => 'date',
        'professional_license_expires_at'=> 'date',
        'extra_documents'            => 'array',
    ];


    protected static function booted(): void
    {
        static::saved(function (EmployerProfile $profile) {
            if (!$profile->company_id) return;

            $sync = [
                'name'           => $profile->company_name,
                'company_size'   => $profile->company_size,
                'website'        => $profile->company_website,
                'description'    => $profile->company_description,
                'contact_name'   => $profile->contact_name,
                'contact_email'  => $profile->contact_email,
                'contact_phone'  => $profile->contact_phone,
                'address1'       => $profile->address,
                'city'           => $profile->city,
                'country_code'   => $profile->country_code,
                'logo_path'      => $profile->company_logo,   // if same schema
            ];

            // Only overwrite if the field exists on companies
            $sync = array_filter($sync, fn($v) => $v !== null);

            if (!empty($sync)) {
                $profile->company()->update($sync);
            }
        });
    }

    // =================================================================
    // Relationships
    // =================================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosts()
    {
        return $this->hasManyThrough(
            \App\Models\Job\JobPost::class,
            \App\Models\Job\Company::class,
            'id',            // companies.id
            'company_id',    // job_posts.company_id
            'company_id',    // employer_profiles.company_id
            'id'             // companies.id
        );
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Job\Company::class, 'company_id');
    }

    public function complianceVerifier()
    {
        return $this->belongsTo(User::class, 'compliance_verified_by');
    }

    public function country()
    {
        return $this->belongsTo(\App\Models\Job\Country::class, 'country_code', 'code');
    }

    // =================================================================
    // Scopes
    // =================================================================
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnboarded($query)
    {
        return $query->where('onboarding_complete', true);
    }

    public function scopeCompliancePending($query)
    {
        return $query->where('compliance_status', 'submitted');
    }

    public function scopeComplianceVerified($query)
    {
        return $query->where('compliance_status', 'verified');
    }

    // =================================================================
    // Accessors
    // =================================================================
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_code,
        ]));
    }

    public function getLogoUrlAttribute(): string
    {
        // 1. Linked Company's logo (primary source)
        if ($this->company_id && $this->company && $this->company->logo_path) {
            $url = $this->company->logo_url;
            if ($url && !str_contains($url, 'blank.png')) {
                return $url;
            }
        }

        // 2. Fallback: profile's own logo column (if used)
        if ($this->company_logo) {
            return str_starts_with($this->company_logo, 'http')
                ? $this->company_logo
                : asset('storage/' . $this->company_logo);
        }

        // 3. Final fallback
        return asset('assets/media/avatars/blank.png');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?: $this->company_name ?: 'Unnamed Company';
    }

    // =================================================================
    // Document URL accessors — one per document type
    // =================================================================
    public function getCertIncorporationUrlAttribute(): ?string
    {
        return $this->documentUrl($this->cert_incorporation_path);
    }

    public function getTradingLicenseUrlAttribute(): ?string
    {
        return $this->documentUrl($this->trading_license_path);
    }

    public function getTinCertificateUrlAttribute(): ?string
    {
        return $this->documentUrl($this->tin_certificate_path);
    }

    public function getNssfCertificateUrlAttribute(): ?string
    {
        return $this->documentUrl($this->nssf_certificate_path);
    }

    public function getTaxClearanceUrlAttribute(): ?string
    {
        return $this->documentUrl($this->tax_clearance_path);
    }

    public function getInsuranceUrlAttribute(): ?string
    {
        return $this->documentUrl($this->insurance_path);
    }

    public function getProfessionalLicenseUrlAttribute(): ?string
    {
        return $this->documentUrl($this->professional_license_path);
    }

    /**
     * Helper for turning a stored path into a public URL.
     */
    protected function documentUrl(?string $path): ?string
    {
        if (!$path) return null;
        return str_starts_with($path, 'http')
            ? $path
            : Storage::disk('public')->url($path);
    }

    // =================================================================
    // Compliance helpers
    // =================================================================

    /**
     * What's required for a Ugandan employer? Extend per country as needed.
     */
    public static function requiredDocumentsFor(string $countryCode = 'UG'): array
    {
        $base = [
            'cert_incorporation' => 'Certificate of Incorporation',
            'trading_license'    => 'Trading License',
            'tin_certificate'    => 'TIN Certificate',
            'tax_clearance'      => 'Tax Clearance',
        ];

        return match (strtoupper($countryCode)) {
            'UG' => $base + [
                'nssf_certificate' => 'NSSF Registration',
            ],
            'KE' => $base + [
                'nssf_certificate' => 'NSSF Registration',
                'nhif_certificate' => 'NHIF Registration',
            ],
            default => $base,
        };
    }

    /**
     * Which required documents are present?
     */
    public function getMissingDocumentsAttribute(): array
    {
        $required = self::requiredDocumentsFor($this->country_code ?? 'UG');
        $missing = [];

        foreach ($required as $key => $label) {
            $pathField = "{$key}_path";
            if (empty($this->$pathField)) {
                $missing[$key] = $label;
            }
        }

        return $missing;
    }

    public function getHasAllRequiredDocumentsAttribute(): bool
    {
        return empty($this->missing_documents);
    }

    /**
     * Compliance percentage (0-100) based on required documents present.
     */
    public function getComplianceProgressAttribute(): int
    {
        $required = self::requiredDocumentsFor($this->country_code ?? 'UG');
        if (empty($required)) return 100;

        $total = count($required);
        $have  = 0;
        foreach ($required as $key => $label) {
            if (!empty($this->{"{$key}_path"})) {
                $have++;
            }
        }

        return (int) round(($have / $total) * 100);
    }

    /**
     * Are any uploaded documents expired or expiring soon (within 30 days)?
     */
    public function getExpiringDocumentsAttribute(): array
    {
        $expiryFields = [
            'cert_incorporation_expires_at'   => 'Certificate of Incorporation',
            'trading_license_expires_at'      => 'Trading License',
            'tax_clearance_expires_at'        => 'Tax Clearance',
            'insurance_expires_at'            => 'Insurance',
            'professional_license_expires_at' => 'Professional License',
        ];

        $expiring = [];
        $soon = now()->addDays(30);

        foreach ($expiryFields as $field => $label) {
            $date = $this->$field;
            if (!$date) continue;

            if ($date->isPast()) {
                $expiring[$field] = ['label' => $label, 'expires_at' => $date, 'status' => 'expired'];
            } elseif ($date->lessThanOrEqualTo($soon)) {
                $expiring[$field] = ['label' => $label, 'expires_at' => $date, 'status' => 'expiring_soon'];
            }
        }

        return $expiring;
    }

    // =================================================================
    // Subscription helpers
    // =================================================================
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    public function canPostJob(): bool
    {
        return $this->can_post_job;   // delegates to the accessor
    }

    // =================================================================
    // Onboarding helpers
    // =================================================================
    public function needsOnboarding(): bool
    {
        return !$this->onboarding_complete;
    }

    /**
     * Which onboarding step should the employer be on?
     * Returns: 'company' | 'contact' | 'documents' | 'review' | null (done)
     */
    public function nextOnboardingStep(): ?string
    {
        if (!$this->company_name || !$this->industry || !$this->company_size) {
            return 'company';
        }
        if (!$this->contact_name || !$this->contact_email || !$this->contact_phone) {
            return 'contact';
        }
        if (!$this->has_all_required_documents) {
            return 'documents';
        }
        if (!$this->onboarding_complete) {
            return 'review';
        }
        return null;
    }

    public function getTierAttribute(): string
    {
        // Tier 3: verified + full docs, no expired documents
        if ($this->is_verified
            && $this->has_all_required_documents
            && empty($this->expiring_documents)) {
            return 'trusted';
        }

        // Tier 2: at least Certificate of Incorporation OR Trading License + TIN
        $hasCore = $this->cert_incorporation_path || $this->trading_license_path;
        if ($hasCore && $this->tin_certificate_path) {
            return 'verified';
        }

        // Tier 1: default for everyone else
        return 'starter';
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'trusted'  => 'Trusted Employer',
            'verified' => 'Verified Employer',
            default    => 'Starter',
        };
    }

    public function getActiveJobLimitAttribute(): ?int
    {
        return match ($this->tier) {
            'trusted'  => null,     // unlimited
            'verified' => 10,
            default    => 1,
        };
    }

    public function getActiveJobsCountAttribute(): int
    {
        if (!$this->company_id) return 0;

        return $this->company
            ->jobPosts()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where(function ($q) {
                $q->whereNull('deadline')
                ->orWhere('deadline', '>=', now());
            })
            ->count();
    }

    public function getCanPostJobAttribute(): bool
    {
        if (!$this->is_active) return false;
        $limit = $this->active_job_limit;
        if ($limit === null) return true;   // unlimited
        return $this->active_jobs_count < $limit;
    }
}