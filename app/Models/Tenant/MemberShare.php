<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'shares_held',
        'par_value',
        'total_value',
        'percentage_of_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shares_held' => 'integer',
            'par_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'percentage_of_total' => 'decimal:4',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
