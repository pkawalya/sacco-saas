<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Report definition for custom report builder (Sprint 4.2).
 *
 * @property int $id
 * @property string $report_code
 * @property string $report_name
 * @property string $report_type
 * @property array $columns
 * @property bool $is_active
 */
class ReportDefinition extends Model
{
    public const TYPE_FINANCIAL = 'financial_statement';

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_REGULATORY = 'regulatory';

    public const TYPE_DASHBOARD = 'dashboard';

    public const TYPES = [
        self::TYPE_FINANCIAL => 'Financial Statement',
        self::TYPE_CUSTOM => 'Custom Report',
        self::TYPE_REGULATORY => 'Regulatory Report',
        self::TYPE_DASHBOARD => 'Dashboard Widget',
    ];

    public const CATEGORIES = [
        'income_statement' => 'Income Statement',
        'balance_sheet' => 'Balance Sheet',
        'cash_flow' => 'Cash Flow Statement',
        'trial_balance' => 'Trial Balance',
        'custom' => 'Custom',
    ];

    protected $fillable = [
        'report_code',
        'report_name',
        'report_type',
        'category',
        'columns',
        'filters',
        'parameters',
        'output_format',
        'is_scheduled',
        'schedule_cron',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'filters' => 'array',
            'parameters' => 'array',
            'is_scheduled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
