<?php

namespace App\Services\Tenant;

use App\Models\Tenant\OfflineSyncQueue;
use Illuminate\Support\Collection;

/**
 * Offline sync engine (FR-CH-040–042).
 */
class OfflineSyncService
{
    /**
     * Queue an offline batch for sync.
     *
     * @param  array<int, mixed>  $transactions
     */
    public function queueBatch(string $branchCode, array $transactions, ?string $deviceId = null): OfflineSyncQueue
    {
        return OfflineSyncQueue::create([
            'branch_code' => $branchCode,
            'device_id' => $deviceId,
            'transaction_count' => count($transactions),
            'payload' => $transactions,
            'sync_status' => OfflineSyncQueue::STATUS_PENDING,
        ]);
    }

    /**
     * Process pending sync batches.
     *
     * @return array{processed: int, synced: int, conflicts: int, failed: int}
     */
    public function processPendingBatches(): array
    {
        $stats = ['processed' => 0, 'synced' => 0, 'conflicts' => 0, 'failed' => 0];

        $batches = OfflineSyncQueue::query()
            ->pending()
            ->orderBy('created_at')
            ->get();

        foreach ($batches as $batch) {
            $stats['processed']++;

            $conflicts = $this->detectConflicts($batch);

            if (count($conflicts) > 0) {
                $batch->markConflict($conflicts);
                $stats['conflicts']++;
            } else {
                $batch->markSynced();
                $stats['synced']++;
            }
        }

        return $stats;
    }

    /**
     * Resolve conflicts for a batch.
     */
    public function resolveConflict(int $queueId, string $strategy, int $resolvedBy): OfflineSyncQueue
    {
        $batch = OfflineSyncQueue::findOrFail($queueId);

        $batch->update([
            'sync_status' => OfflineSyncQueue::STATUS_SYNCED,
            'resolution_strategy' => $strategy,
            'resolved_by' => $resolvedBy,
            'synced_at' => now(),
        ]);

        return $batch->fresh();
    }

    /**
     * Get sync health stats.
     *
     * @return array{total_pending: int, total_synced: int, total_conflicts: int, total_failed: int, by_branch: Collection}
     */
    public function getSyncHealth(): array
    {
        $all = OfflineSyncQueue::all();

        return [
            'total_pending' => $all->where('sync_status', OfflineSyncQueue::STATUS_PENDING)->count(),
            'total_synced' => $all->where('sync_status', OfflineSyncQueue::STATUS_SYNCED)->count(),
            'total_conflicts' => $all->where('sync_status', OfflineSyncQueue::STATUS_CONFLICT)->count(),
            'total_failed' => $all->where('sync_status', OfflineSyncQueue::STATUS_FAILED)->count(),
            'by_branch' => $all->groupBy('branch_code')->map->count(),
        ];
    }

    /**
     * Detect conflicts in an offline batch (stub — would validate against server state in production).
     *
     * @return array<int, string>
     */
    private function detectConflicts(OfflineSyncQueue $batch): array
    {
        // In production, each transaction in payload would be checked against
        // server state. For now, we detect conflict if a duplicate device
        // batch exists in 'synced' status with the same transaction_count.
        $duplicateExists = OfflineSyncQueue::query()
            ->where('device_id', $batch->device_id)
            ->where('transaction_count', $batch->transaction_count)
            ->where('sync_status', OfflineSyncQueue::STATUS_SYNCED)
            ->where('id', '!=', $batch->id)
            ->exists();

        if ($duplicateExists) {
            return ['Possible duplicate batch detected from same device'];
        }

        return [];
    }
}
