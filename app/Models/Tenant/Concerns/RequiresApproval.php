<?php

namespace App\Models\Tenant\Concerns;

use App\Models\Tenant\ApprovalRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Attach to models that require maker-checker dual approval.
 *
 * Usage: `use RequiresApproval;` in your model, then call
 * `$model->requestApproval('create', $payload)` instead of direct save.
 */
trait RequiresApproval
{
    /**
     * Submit a change for approval.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function requestApproval(string $action, ?array $payload = null): ApprovalRequest
    {
        return ApprovalRequest::create([
            'approvable_type' => $this->getMorphClass(),
            'approvable_id' => $this->getKey() ?? 0,
            'action' => $action,
            'payload' => $payload,
            'requested_by' => auth()->id(),
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @return MorphMany<ApprovalRequest, $this>
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Check if this model has a pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvalRequests()
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->exists();
    }
}
