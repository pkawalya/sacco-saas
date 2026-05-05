<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chart_of_accounts';

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_ASSET => 'Asset',
        self::TYPE_LIABILITY => 'Liability',
        self::TYPE_EQUITY => 'Equity',
        self::TYPE_REVENUE => 'Revenue',
        self::TYPE_EXPENSE => 'Expense',
    ];

    public const BALANCE_DEBIT = 'debit';

    public const BALANCE_CREDIT = 'credit';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'account_sub_type',
        'parent_id',
        'level',
        'is_header',
        'normal_balance',
        'currency_code',
        'is_bank_account',
        'is_cash_account',
        'is_system_account',
        'is_active',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_header' => 'boolean',
            'is_bank_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'is_system_account' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ──────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopePostable(Builder $query): void
    {
        $query->where('is_header', false)->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('account_type', $type);
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Get the full path label (e.g. "Assets > Current Assets > Cash").
     */
    public function getPathAttribute(): string
    {
        $parts = [$this->account_name];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($parts, $current->account_name);
        }

        return implode(' > ', $parts);
    }

    /**
     * Whether this account normal balance is debit side.
     */
    public function isDebitNormal(): bool
    {
        return $this->normal_balance === self::BALANCE_DEBIT;
    }
}
