<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail record for regulatory compliance.
 *
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property int|null $user_id
 * @property array|null $old_values
 * @property array|null $new_values
 */
class AuditTrail extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'user_id',
        'user_email',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the parent auditable model.
     *
     * @return MorphTo<Model, self>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
