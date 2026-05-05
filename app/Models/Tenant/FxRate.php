<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Foreign exchange rate.
 *
 * @property int $id
 * @property string $base_currency
 * @property string $quote_currency
 * @property float $buy_rate
 * @property float $sell_rate
 * @property float $mid_rate
 */
class FxRate extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'buy_rate',
        'sell_rate',
        'mid_rate',
        'spread',
        'source',
        'effective_date',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buy_rate' => 'decimal:6',
            'sell_rate' => 'decimal:6',
            'mid_rate' => 'decimal:6',
            'spread' => 'decimal:6',
            'effective_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Convert amount from base to quote currency.
     */
    public function convert(float $amount, string $direction = 'sell'): float
    {
        $rate = $direction === 'buy' ? (float) $this->buy_rate : (float) $this->sell_rate;

        return round($amount * $rate, 2);
    }

    /**
     * Get latest rate for a currency pair.
     */
    public static function getLatest(string $quoteCurrency, string $baseCurrency = 'UGX'): ?self
    {
        return static::query()
            ->where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->first();
    }
}
