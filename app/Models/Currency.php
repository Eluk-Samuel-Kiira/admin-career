<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'base_unit_multiplier',
        'exchange_rate_to_usd',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'base_unit_multiplier' => 'integer',
        'exchange_rate_to_usd' => 'decimal:6',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function transactionLogs()
    {
        return $this->hasMany(PaymentTransactionLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Convert from display amount to cents/base units
     * For UGX (decimal_places=0): 1,000,000 UGX = 1,000,000
     * For USD (decimal_places=2): 100.00 USD = 10,000 cents
     */
    public function toCents(float $amount): int
    {
        if ($this->decimal_places === 0) {
            return (int) round($amount);
        }
        return (int) round($amount * $this->base_unit_multiplier);
    }

    /**
     * Convert from cents/base units to display amount
     * For UGX (decimal_places=0): 1,000,000 = 1,000,000 UGX
     * For USD (decimal_places=2): 10,000 cents = 100.00 USD
     */
    public function fromCents(int $cents): float
    {
        if ($this->decimal_places === 0) {
            return (float) $cents;
        }
        return $cents / $this->base_unit_multiplier;
    }

    /**
     * Format amount for display
     */
    public function formatAmount(?int $cents): string
    {
        // Handle null values
        if ($cents === null) {
            $cents = 0;
        }
        
        $amount = $this->fromCents($cents);
        
        if ($this->decimal_places === 0) {
            return $this->symbol . ' ' . number_format($amount, 0);
        }
        
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }

    /**
     * Get the default currency
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Get currency by code
     */
    public static function getByCode(string $code)
    {
        return self::where('code', $code)->first();
    }
}