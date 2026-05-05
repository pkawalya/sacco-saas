<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanCollateral extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_LAND_TITLE = 'land_title';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_BUILDING = 'building';

    public const TYPE_EQUIPMENT = 'equipment';

    public const TYPE_STOCK = 'stock';

    public const TYPE_LIVESTOCK = 'livestock';

    public const TYPES = [
        self::TYPE_LAND_TITLE => 'Land Title',
        self::TYPE_VEHICLE => 'Vehicle',
        self::TYPE_BUILDING => 'Building/Property',
        self::TYPE_EQUIPMENT => 'Equipment',
        self::TYPE_STOCK => 'Stock/Inventory',
        self::TYPE_LIVESTOCK => 'Livestock',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RELEASED = 'released';

    public const STATUS_FORECLOSED = 'foreclosed';

    protected $fillable = [
        'loan_id',
        'member_id',
        'asset_type',
        'asset_description',
        'asset_identifier',
        'location',
        'estimated_value',
        'forced_sale_value',
        'valuation_date',
        'valuer_name',
        'is_insured',
        'insurance_company',
        'policy_number',
        'insurance_expiry_date',
        'insurance_cover_amount',
        'status',
        'documents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'forced_sale_value' => 'decimal:2',
            'insurance_cover_amount' => 'decimal:2',
            'valuation_date' => 'date',
            'insurance_expiry_date' => 'date',
            'is_insured' => 'boolean',
            'documents' => 'array',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isInsuranceExpiringSoon(int $withinDays = 30): bool
    {
        if (! $this->is_insured || ! $this->insurance_expiry_date) {
            return false;
        }

        return $this->insurance_expiry_date->diffInDays(now(), false) >= -$withinDays
            && $this->insurance_expiry_date->isFuture();
    }

    public function isInsuranceExpired(): bool
    {
        return $this->is_insured
            && $this->insurance_expiry_date !== null
            && $this->insurance_expiry_date->isPast();
    }
}
