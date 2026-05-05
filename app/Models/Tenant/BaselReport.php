<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Basel III regulatory report.
 *
 * @property int $id
 * @property string $report_ref
 * @property string $report_type
 * @property float $car_ratio
 * @property bool $is_compliant
 */
class BaselReport extends Model
{
    public const TYPE_CAPITAL_ADEQUACY = 'capital_adequacy';

    public const TYPE_LIQUIDITY_COVERAGE = 'liquidity_coverage';

    public const TYPE_NET_STABLE_FUNDING = 'net_stable_funding';

    public const TYPE_LEVERAGE_RATIO = 'leverage_ratio';

    public const TYPES = [
        self::TYPE_CAPITAL_ADEQUACY => 'Capital Adequacy',
        self::TYPE_LIQUIDITY_COVERAGE => 'Liquidity Coverage',
        self::TYPE_NET_STABLE_FUNDING => 'Net Stable Funding',
        self::TYPE_LEVERAGE_RATIO => 'Leverage Ratio',
    ];

    protected $fillable = [
        'report_ref',
        'report_type',
        'reporting_period',
        'tier_1_capital',
        'tier_2_capital',
        'total_capital',
        'risk_weighted_assets',
        'car_ratio',
        'minimum_car',
        'hqla',
        'net_cash_outflows',
        'lcr_ratio',
        'is_compliant',
        'is_submitted',
        'submitted_at',
        'prepared_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier_1_capital' => 'decimal:2',
            'tier_2_capital' => 'decimal:2',
            'total_capital' => 'decimal:2',
            'risk_weighted_assets' => 'decimal:2',
            'car_ratio' => 'decimal:4',
            'minimum_car' => 'decimal:4',
            'hqla' => 'decimal:2',
            'net_cash_outflows' => 'decimal:2',
            'lcr_ratio' => 'decimal:4',
            'is_compliant' => 'boolean',
            'is_submitted' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Compute CAR = Total Capital / Risk-Weighted Assets.
     */
    public function computeCar(): float
    {
        if ((float) $this->risk_weighted_assets === 0.0) {
            return 0;
        }

        $car = round(((float) $this->total_capital / (float) $this->risk_weighted_assets) * 100, 4);

        $this->update([
            'car_ratio' => $car,
            'is_compliant' => $car >= (float) $this->minimum_car,
        ]);

        return $car;
    }

    /**
     * Compute LCR = HQLA / Net Cash Outflows.
     */
    public function computeLcr(): float
    {
        if ((float) $this->net_cash_outflows === 0.0) {
            return 0;
        }

        $lcr = round(((float) $this->hqla / (float) $this->net_cash_outflows) * 100, 4);
        $this->update(['lcr_ratio' => $lcr]);

        return $lcr;
    }
}
