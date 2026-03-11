<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MemberGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'group_code',
        'branch_code',
        'status',
        'description',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_group_member')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the group officers (chairperson, secretary, treasurer).
     */
    public function officers(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['chairperson', 'secretary', 'treasurer']);
    }
}
