<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AmortisationSchedule;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanGuarantor;
use App\Models\Tenant\LoanProduct;
use App\Models\Tenant\LoanRepayment;
use App\Models\Tenant\SavingsAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Core loan service: amortisation, DSCR, guarantor lock/release, repayment allocation.
 *
 * Covers FR-LM-002, FR-LM-020, FR-LM-021, FR-LM-030, FR-LM-031, FR-LM-032
 */
class LoanService
{
    /**
     * FR-LM-002: Generate an amortisation schedule for a reducing-balance or flat loan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateSchedule(
        float $principal,
        float $annualRatePercent,
        int $tenureMonths,
        Carbon $firstRepaymentDate,
        string $method = LoanProduct::METHOD_REDUCING,
        float $monthlyMaintenanceFee = 0.0
    ): Collection {
        $schedule = collect();
        $monthlyRate = ($annualRatePercent / 100) / 12;
        $balance = $principal;

        if ($method === LoanProduct::METHOD_FLAT) {
            $totalInterest = $principal * ($annualRatePercent / 100) * ($tenureMonths / 12);
            $monthlyInstalment = round(($principal + $totalInterest) / $tenureMonths, 2);
            $monthlyInterest = round($totalInterest / $tenureMonths, 2);
            $monthlyPrincipal = round($principal / $tenureMonths, 2);
        } else {
            // Reducing balance: PMT formula
            if ($monthlyRate > 0) {
                $monthlyInstalment = round(
                    $principal * ($monthlyRate * pow(1 + $monthlyRate, $tenureMonths))
                    / (pow(1 + $monthlyRate, $tenureMonths) - 1),
                    2
                );
            } else {
                $monthlyInstalment = round($principal / $tenureMonths, 2);
            }
        }

        for ($i = 1; $i <= $tenureMonths; $i++) {
            $dueDate = $firstRepaymentDate->copy()->addMonths($i - 1);

            if ($method === LoanProduct::METHOD_FLAT) {
                $interestDue = $monthlyInterest;
                $principalDue = ($i === $tenureMonths)
                    ? round($balance, 2)
                    : $monthlyPrincipal;
            } else {
                $interestDue = round($balance * $monthlyRate, 2);
                $principalDue = ($i === $tenureMonths)
                    ? round($balance, 2)
                    : min($monthlyInstalment - $interestDue, $balance);
            }

            $totalDue = round($principalDue + $interestDue + $monthlyMaintenanceFee, 2);
            $closingBalance = round($balance - $principalDue, 2);

            $schedule->push([
                'instalment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'opening_balance' => round($balance, 2),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
                'maintenance_fee_due' => $monthlyMaintenanceFee,
                'total_due' => $totalDue,
                'closing_balance' => max(0, $closingBalance),
                'status' => AmortisationSchedule::STATUS_SCHEDULED,
            ]);

            $balance = max(0, $closingBalance);
        }

        return $schedule;
    }

    /**
     * Persist the generated schedule to the database.
     */
    public function persistSchedule(Loan $loan, Collection $schedule): void
    {
        $loan->schedule()->delete();

        foreach ($schedule as $row) {
            AmortisationSchedule::create(array_merge(['loan_id' => $loan->id], $row));
        }
    }

    /**
     * FR-LM-020: Validate that a guarantor has sufficient savings coverage.
     *
     * @return array<int, string> List of block reasons (empty = eligible)
     */
    public function validateGuarantorEligibility(
        SavingsAccount $savingsAccount,
        float $guaranteeAmount
    ): array {
        $blocks = [];

        if ((float) $savingsAccount->available_balance < $guaranteeAmount) {
            $available = number_format((float) $savingsAccount->available_balance, 2);
            $required = number_format($guaranteeAmount, 2);
            $blocks[] = "Guarantor's available savings balance (UGX {$available}) is less than the required guarantee amount (UGX {$required}).";
        }

        if ($savingsAccount->status !== SavingsAccount::STATUS_ACTIVE) {
            $blocks[] = "Guarantor's savings account is not active (status: {$savingsAccount->status}).";
        }

        return $blocks;
    }

    /**
     * FR-LM-021: Lock guarantor savings when loan is disbursed.
     */
    public function lockGuarantorSavings(LoanGuarantor $guarantor): void
    {
        $account = $guarantor->guaranteedSavingsAccount;

        if (! $account) {
            return;
        }

        $lockAmount = (float) $guarantor->guaranteed_amount;
        $account->applyHold($lockAmount);

        $guarantor->update([
            'original_savings_balance' => $account->ledger_balance,
            'locked_amount' => $lockAmount,
            'status' => LoanGuarantor::STATUS_ACTIVE,
        ]);
    }

    /**
     * FR-LM-021: Release guarantor savings when loan is fully repaid.
     */
    public function releaseGuarantorSavings(LoanGuarantor $guarantor, string $reason = 'Loan fully repaid'): void
    {
        $account = $guarantor->guaranteedSavingsAccount;

        if ($account) {
            $account->releaseHold((float) $guarantor->locked_amount);
        }

        $guarantor->update([
            'locked_amount' => 0,
            'status' => LoanGuarantor::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
            'release_reason' => $reason,
        ]);
    }

    /**
     * FR-LM-030 / FR-LM-031: Process a loan repayment and allocate across penalty, interest, principal.
     *
     * @throws \RuntimeException
     */
    public function processRepayment(
        Loan $loan,
        float $amountPaid,
        string $channel = LoanRepayment::CHANNEL_BRANCH,
        ?string $referenceNumber = null,
        ?int $processedBy = null
    ): LoanRepayment {
        if ($amountPaid <= 0) {
            throw new \RuntimeException('Repayment amount must be positive.');
        }

        $remaining = $amountPaid;

        // FR-LM-030: Configurable allocation order — penalty → interest → principal
        $allocatedPenalty = 0.0;
        $allocatedInterest = 0.0;
        $allocatedPrincipal = 0.0;

        // 1. Clear penalty first
        $penaltyOutstanding = (float) $loan->outstanding_penalty;

        if ($penaltyOutstanding > 0 && $remaining > 0) {
            $allocatedPenalty = min($remaining, $penaltyOutstanding);
            $remaining -= $allocatedPenalty;
        }

        // 2. Clear interest
        $interestOutstanding = (float) $loan->outstanding_interest;

        if ($interestOutstanding > 0 && $remaining > 0) {
            $allocatedInterest = min($remaining, $interestOutstanding);
            $remaining -= $allocatedInterest;
        }

        // 3. Clear principal
        $principalOutstanding = (float) $loan->outstanding_principal;

        if ($principalOutstanding > 0 && $remaining > 0) {
            $allocatedPrincipal = min($remaining, $principalOutstanding);
            $remaining -= $allocatedPrincipal;
        }

        $excessAmount = max(0, $remaining);

        // Update loan balances
        $newPrincipal = max(0, $principalOutstanding - $allocatedPrincipal);
        $newInterest = max(0, $interestOutstanding - $allocatedInterest);
        $newPenalty = max(0, $penaltyOutstanding - $allocatedPenalty);
        $newTotal = $newPrincipal + $newInterest + $newPenalty;

        $loan->update([
            'outstanding_principal' => $newPrincipal,
            'outstanding_interest' => $newInterest,
            'outstanding_penalty' => $newPenalty,
            'total_outstanding' => $newTotal,
            'last_repayment_date' => now()->toDateString(),
            'status' => $newTotal <= 0 ? Loan::STATUS_COMPLETED : $loan->status,
            'actual_maturity_date' => $newTotal <= 0 ? now()->toDateString() : $loan->actual_maturity_date,
        ]);

        // Record repayment
        $repayment = LoanRepayment::create([
            'receipt_number' => 'RCP-'.strtoupper(Str::random(10)),
            'loan_id' => $loan->id,
            'member_id' => $loan->member_id,
            'amount_paid' => $amountPaid,
            'channel' => $channel,
            'reference_number' => $referenceNumber,
            'allocated_to_penalty' => $allocatedPenalty,
            'allocated_to_interest' => $allocatedInterest,
            'allocated_to_principal' => $allocatedPrincipal,
            'allocated_to_fees' => 0,
            'excess_amount' => $excessAmount,
            'outstanding_after' => $newTotal,
            'processed_by' => $processedBy,
            'value_date' => now()->toDateString(),
            'posted_at' => now(),
        ]);

        // Update amortisation schedule
        $this->markInstalmentsPaid($loan, $allocatedPrincipal, $allocatedInterest);

        return $repayment;
    }

    /**
     * FR-LM-032: Recompute PAR (days past due) for a single loan.
     */
    public function recomputePar(Loan $loan): void
    {
        if (! in_array($loan->status, [Loan::STATUS_ACTIVE])) {
            return;
        }

        // Find the earliest unpaid or partial instalment that is past due
        $overdueInstalment = $loan->schedule()
            ->whereIn('status', [AmortisationSchedule::STATUS_SCHEDULED, AmortisationSchedule::STATUS_PARTIAL])
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->first();

        if (! $overdueInstalment) {
            $loan->update([
                'days_past_due' => 0,
                'par_bucket' => 'current',
                'amount_in_arrears' => 0,
            ]);

            return;
        }

        $dpd = (int) Carbon::parse($overdueInstalment->due_date)->diffInDays(now());
        $bucket = Loan::computeParBucket($dpd);

        $arrears = $loan->schedule()
            ->whereIn('status', [AmortisationSchedule::STATUS_SCHEDULED, AmortisationSchedule::STATUS_PARTIAL])
            ->where('due_date', '<', now()->toDateString())
            ->sum('total_due') - $loan->schedule()
            ->whereIn('status', [AmortisationSchedule::STATUS_SCHEDULED, AmortisationSchedule::STATUS_PARTIAL])
            ->where('due_date', '<', now()->toDateString())
            ->sum('total_paid');

        $loan->update([
            'days_past_due' => $dpd,
            'par_bucket' => $bucket,
            'amount_in_arrears' => max(0, $arrears),
        ]);
    }

    /**
     * After processing a repayment, update the relevant schedule instalments.
     */
    protected function markInstalmentsPaid(Loan $loan, float $principalPaid, float $interestPaid): void
    {
        $remainingPrincipal = $principalPaid;
        $remainingInterest = $interestPaid;

        $unpaidInstalments = $loan->schedule()
            ->whereIn('status', [AmortisationSchedule::STATUS_SCHEDULED, AmortisationSchedule::STATUS_PARTIAL])
            ->orderBy('due_date')
            ->get();

        foreach ($unpaidInstalments as $instalment) {
            if ($remainingPrincipal <= 0 && $remainingInterest <= 0) {
                break;
            }

            $intApplied = min($remainingInterest, (float) $instalment->interest_due - (float) $instalment->interest_paid);
            $prinApplied = min($remainingPrincipal, (float) $instalment->principal_due - (float) $instalment->principal_paid);

            $newInterestPaid = (float) $instalment->interest_paid + $intApplied;
            $newPrincipalPaid = (float) $instalment->principal_paid + $prinApplied;
            $newTotalPaid = $newInterestPaid + $newPrincipalPaid + (float) $instalment->penalty_paid;

            $isFullyPaid = $newTotalPaid >= (float) $instalment->total_due - 0.01;

            $instalment->update([
                'interest_paid' => $newInterestPaid,
                'principal_paid' => $newPrincipalPaid,
                'total_paid' => $newTotalPaid,
                'status' => $isFullyPaid ? AmortisationSchedule::STATUS_PAID : AmortisationSchedule::STATUS_PARTIAL,
                'paid_date' => $isFullyPaid ? now()->toDateString() : $instalment->paid_date,
            ]);

            $remainingInterest -= $intApplied;
            $remainingPrincipal -= $prinApplied;
        }
    }
}
