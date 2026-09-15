<?php

namespace App\Models\Service;

use App\Models\Currency;
use App\Models\Job\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    use HasFactory;

    public const GLOBAL_CODE = 'GLOBAL';

    protected $fillable = [
        'service_id',
        'country_code',
        'currency_id',
        'amount_cents',
        'interval',
        'is_active',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'is_active'    => 'boolean',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Country resolved by its code (Country model uses 'code' not 'id').
     * Located in App\Models\Job\Country.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('country_code', self::GLOBAL_CODE);
    }

    public function scopeForCountry($query, string $code)
    {
        return $query->where('country_code', strtoupper($code));
    }

    // ---------------------------------------------------------------
    // Amount helpers — delegate to Currency
    // ---------------------------------------------------------------

    /**
     * Convert a decimal display amount (e.g. 25.00 USD or 95000 UGX)
     * to the correct base-unit cents using the assigned currency.
     */
    public function amountToCents(float $amount): int
    {
        if (!$this->currency) {
            return (int) round($amount * 100);
        }
        return $this->currency->toCents($amount);
    }

    /**
     * Find the effective active price for a service in a given country,
     * falling back to the GLOBAL price if no country-specific price exists.
     *
     * @param  string  $serviceKey   e.g. 'cv_review'
     * @param  string  $countryCode  e.g. 'UG' or 'KE'
     * @return ServicePrice|null
     */
    public static function resolve(string $serviceKey, string $countryCode): ?self
    {
        $service = Service::where('key', $serviceKey)->where('is_active', true)->first();
        if (!$service) {
            return null;
        }

        $countryCode = strtoupper($countryCode);

        // Country-specific first
        $price = self::with(['currency'])
            ->active()
            ->where('service_id', $service->id)
            ->where('country_code', $countryCode)
            ->first();

        // Fall back to GLOBAL
        if (!$price) {
            $price = self::with(['currency'])
                ->active()
                ->where('service_id', $service->id)
                ->where('country_code', self::GLOBAL_CODE)
                ->first();
        }

        return $price;
    }

    /**
     * Convert stored cents back to a decimal display amount.
     */
    public function centsToAmount(?int $cents): float
    {
        if (!$this->currency) {
            return ($cents ?? 0) / 100;
        }
        return $this->currency->fromCents($cents ?? 0);
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    /**
     * Human-readable price, respecting the currency's decimal places
     * and symbol (e.g. "USh 95,000" for UGX, "$ 25.00" for USD).
     */
    public function getFormattedAmountAttribute(): string
    {
        if (!$this->currency) {
            return '—';
        }

        $price = $this->currency->formatAmount($this->amount_cents);

        if ($this->interval) {
            $price .= ' / ' . $this->interval;
        }

        return $price;
    }

    /**
     * Display amount as a float, e.g. 25.0 for USD, 95000.0 for UGX.
     * Used by the edit modal to prefill the amount input.
     */
    public function getAmountAttribute(): float
    {
        return $this->centsToAmount($this->amount_cents);
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="badge badge-light-success">Active</span>'
            : '<span class="badge badge-light-danger">Inactive</span>';
    }

    public function getCountryLabelAttribute(): string
    {
        if ($this->country_code === self::GLOBAL_CODE) {
            return '<span class="badge badge-light-info">🌍 Global Fallback</span>';
        }

        $flag = $this->country->flag ?? '🌍';
        $name = $this->country->name ?? $this->country_code;

        return "{$flag} " . e($name);
    }

    public function getIntervalLabelAttribute(): string
    {
        return $this->interval
            ? ucfirst($this->interval) . 'ly'
            : '—';
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    public static function normalizeBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            return in_array(strtolower($value), ['on', '1', 'true', 'yes'], true);
        }
        return (bool) $value;
    }
}