<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 4-level cost centre hierarchy with historical data preservation.
 *
 * FR-CC-001: Division > Department > Branch > Unit
 * FR-CC-002: CRUD with deactivation audit trail
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $level
 * @property int|null $parent_id
 * @property string|null $manager_name
 * @property int|null $manager_user_id
 * @property bool $is_active
 * @property string|null $deactivated_at
 * @property int|null $deactivated_by
 * @property string|null $deactivation_reason
 */
class CostCentre extends Model
{
    use SoftDeletes;

    // ─── Level Constants (FR-CC-001) ────────────────────────────
    public const LEVEL_DIVISION = 1;

    public const LEVEL_DEPARTMENT = 2;

    public const LEVEL_BRANCH = 3;

    public const LEVEL_UNIT = 4;

    public const LEVELS = [
        self::LEVEL_DIVISION => 'Division',
        self::LEVEL_DEPARTMENT => 'Department',
        self::LEVEL_BRANCH => 'Branch',
        self::LEVEL_UNIT => 'Unit',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'level',
        'parent_id',
        'manager_name',
        'manager_user_id',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    /**
     * Recursively load all descendants.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfLevel(Builder $query, int $level): void
    {
        $query->where('level', $level);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Get the full hierarchy path (e.g. "Finance Division > Accounting Dept > HQ Branch").
     */
    public function getPathAttribute(): string
    {
        $parts = [$this->name];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($parts, $current->name);
        }

        return implode(' > ', $parts);
    }

    /**
     * Get the level label.
     */
    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? 'Unknown';
    }

    /**
     * Deactivate this cost centre with audit trail (FR-CC-002).
     */
    public function deactivate(int $userId, string $reason): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $userId,
            'deactivation_reason' => $reason,
        ]);
    }

    /**
     * Reactivate a previously deactivated cost centre.
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
            'deactivation_reason' => null,
        ]);
    }

    /**
     * Validate that level is correct relative to parent.
     *
     * @throws \RuntimeException
     */
    public function validateHierarchy(): void
    {
        if ($this->parent_id === null && $this->level !== self::LEVEL_DIVISION) {
            throw new \RuntimeException('Root cost centres must be at Division level (1).');
        }

        if ($this->parent_id !== null) {
            $parent = self::findOrFail($this->parent_id);
            if ($this->level !== $parent->level + 1) {
                throw new \RuntimeException(
                    'Child level must be exactly one below parent. Expected '.($parent->level + 1).", got {$this->level}."
                );
            }
        }
    }

    /**
     * Get all IDs in this subtree (self + descendants).
     *
     * @return array<int>
     */
    public function getSubtreeIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getSubtreeIds());
        }

        return $ids;
    }
}
