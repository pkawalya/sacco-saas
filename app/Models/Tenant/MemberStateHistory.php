<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStateHistory extends Model
{
    protected $table = 'member_state_history';

    protected $fillable = [
        'member_id',
        'from_state',
        'to_state',
        'reason_code',
        'notes',
        'acted_by',
        'transitioned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transitioned_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
