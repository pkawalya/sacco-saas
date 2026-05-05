<?php

namespace App\Models\Tenant\Concerns;

use App\Models\Tenant\AuditTrail;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Attach to any Eloquent model to automatically log create/update/delete.
 *
 * Usage: `use Auditable;` in your model.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }
            $old = collect($dirty)->mapWithKeys(fn ($v, $k) => [$k => $model->getOriginal($k)])->all();
            static::logAudit($model, 'updated', $old, $dirty);
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getAttributes(), []);
        });
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    protected static function logAudit($model, string $event, array $old, array $new): void
    {
        // Strip sensitive fields from audit logs
        $sensitiveFields = ['password', 'remember_token', 'card_number'];
        foreach ($sensitiveFields as $field) {
            unset($old[$field], $new[$field]);
        }

        $user = auth()->user();

        AuditTrail::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * @return MorphMany<AuditTrail, $this>
     */
    public function auditTrails(): MorphMany
    {
        return $this->morphMany(AuditTrail::class, 'auditable');
    }
}
