<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Escalation chain definition (FR-AN-032).
 *
 * @property int $id
 * @property string $alert_type
 * @property int $tier
 * @property string $recipient_role
 * @property int $escalate_after_minutes
 * @property string $notification_channel
 * @property bool $is_active
 */
class EscalationChain extends Model
{
    protected $table = 'escalation_chains';

    public const ROLES = [
        'officer' => 'Officer',
        'supervisor' => 'Supervisor',
        'manager' => 'Manager',
        'ceo' => 'CEO',
        'cfo' => 'CFO',
    ];

    protected $fillable = [
        'alert_type',
        'tier',
        'recipient_role',
        'escalate_after_minutes',
        'notification_channel',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
