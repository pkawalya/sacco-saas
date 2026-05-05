<?php

namespace App\Console\Commands;

use App\Models\Tenant\Member;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;

class MarkDormantMembers extends Command
{
    use HasATenantsOption;

    /**
     * FR-MM-011: Auto-dormancy with 30-day warning notification.
     *
     * Members with no activity for `--days` days (default 180) are marked dormant.
     * Members approaching the threshold by `--warn-days` days receive a warning flag.
     */
    protected $signature = 'members:mark-dormant
                            {--days=180 : Days of inactivity before marking dormant}
                            {--warn-days=30 : Days before dormancy to flag as warning}
                            {--dry-run : Preview without making changes}';

    protected $description = 'FR-MM-011: Mark active members as dormant after inactivity period (EOD batch)';

    public function handle(): int
    {
        $inactivityDays = (int) $this->option('days');
        $warnDays = (int) $this->option('warn-days');
        $dryRun = $this->option('dry-run');

        $dormancyThreshold = Carbon::now()->subDays($inactivityDays);
        $warningThreshold = Carbon::now()->subDays($inactivityDays - $warnDays);

        /**
         * Find active members whose last activity predates the dormancy threshold.
         *
         * "Last activity" currently uses `updated_at`. When the savings module
         * is built, this query should be updated to check the last transaction date
         * on savings_transactions for each member.
         */
        $dormantCandidates = Member::query()
            ->where('status', Member::STATUS_ACTIVE)
            ->where('updated_at', '<=', $dormancyThreshold)
            ->get();

        $warningCandidates = Member::query()
            ->where('status', Member::STATUS_ACTIVE)
            ->whereBetween('updated_at', [$dormancyThreshold, $warningThreshold])
            ->get();

        $this->info("Dormancy threshold: {$inactivityDays} days (before {$dormancyThreshold->toDateString()})");
        $this->info("Warning threshold: {$warnDays} days before dormancy");
        $this->newLine();

        // ─── Warning notifications ─────────────────────────────
        if ($warningCandidates->isNotEmpty()) {
            $this->warn("Found {$warningCandidates->count()} member(s) approaching dormancy (warning period):");

            foreach ($warningCandidates as $member) {
                $this->line("  • [{$member->member_number}] {$member->full_name} — last active: {$member->updated_at->toDateString()}");

                if (! $dryRun) {
                    // TODO: Dispatch dormancy warning notification when Notifications module is built
                    // DormancyWarningNotification::dispatch($member);
                }
            }
            $this->newLine();
        }

        // ─── Mark dormant ──────────────────────────────────────
        if ($dormantCandidates->isEmpty()) {
            $this->info('No members to mark as dormant.');

            return self::SUCCESS;
        }

        $this->warn("Found {$dormantCandidates->count()} member(s) to mark as dormant:");

        foreach ($dormantCandidates as $member) {
            $this->line("  • [{$member->member_number}] {$member->full_name} — last active: {$member->updated_at->toDateString()}");

            if (! $dryRun) {
                $member->transitionTo(
                    newState: Member::STATUS_DORMANT,
                    reasonCode: 'auto_dormancy',
                    notes: "Auto-marked dormant after {$inactivityDays} days of inactivity by EOD batch.",
                    actedBy: null
                );

                $member->update(['dormant_at' => now()]);

                // TODO: Dispatch dormancy notification when Notifications module is built
                // DormancyNotification::dispatch($member);
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('[DRY RUN] No changes were made.');
        } else {
            $this->newLine();
            $this->info("✔ Marked {$dormantCandidates->count()} member(s) as dormant.");
        }

        return self::SUCCESS;
    }
}
