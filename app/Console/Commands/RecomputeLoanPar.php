<?php

namespace App\Console\Commands;

use App\Models\Tenant\Loan;
use App\Services\Tenant\LoanService;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;

class RecomputeLoanPar extends Command
{
    use HasATenantsOption;

    /**
     * FR-LM-032: EOD PAR (Portfolio at Risk) recomputation.
     *
     * Runs nightly per-tenant. Updates days_past_due and par_bucket for all active loans.
     */
    protected $signature = 'loans:recompute-par
                            {--dry-run : Preview only, no changes}';

    protected $description = 'FR-LM-032: Nightly recompute PAR aging (days past due, buckets) for all active loans';

    public function __construct(protected LoanService $loanService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $activeLoans = Loan::query()
            ->where('status', Loan::STATUS_ACTIVE)
            ->with(['schedule'])
            ->get();

        if ($activeLoans->isEmpty()) {
            $this->info('No active loans found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$activeLoans->count()} active loan(s)...");
        $this->newLine();

        $updated = 0;
        $nowInArrears = 0;

        foreach ($activeLoans as $loan) {
            $previousBucket = $loan->par_bucket;
            $previousDpd = $loan->days_past_due;

            if (! $dryRun) {
                $this->loanService->recomputePar($loan);
                $loan->refresh();
            }

            $newBucket = $loan->par_bucket;

            if ($previousBucket !== $newBucket || $previousDpd !== $loan->days_past_due) {
                $updated++;
                $this->line("  [{$loan->loan_number}] {$previousBucket}/{$previousDpd}d → {$newBucket}/{$loan->days_past_due}d");
            }

            if ($loan->days_past_due > 0) {
                $nowInArrears++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->comment('[DRY RUN] No changes were made.');
        } else {
            $this->info("✔ {$updated} loan(s) updated | {$nowInArrears} loan(s) in arrears");
        }

        return self::SUCCESS;
    }
}
