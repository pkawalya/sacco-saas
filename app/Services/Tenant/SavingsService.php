<?php

namespace App\Services\Tenant;

use App\Models\Tenant\SavingsAccount;
use App\Models\Tenant\SavingsProduct;
use App\Models\Tenant\SavingsTransaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Savings & Deposits core service.
 *
 * Handles deposits, withdrawals, transfers, interest computation,
 * and account closure validation for FR-SD-002, 012, 014, 020.
 */
class SavingsService
{
    /**
     * Generate a unique account number.
     * Format: SAV-[BRANCH]-[YEAR]-[SEQUENCE]
     */
    public function generateAccountNumber(SavingsProduct $product, string $branchCode = 'HQ'): string
    {
        $prefix = strtoupper(substr($product->product_type, 0, 3)).'-'.strtoupper($branchCode).'-'.date('Y').'-';

        $lastAccount = SavingsAccount::query()
            ->where('account_number', 'like', $prefix.'%')
            ->orderByDesc('account_number')
            ->first();

        $nextSequence = $lastAccount
            ? ((int) substr($lastAccount->account_number, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * FR-SD-012: Process a deposit from any channel.
     *
     * @throws \RuntimeException
     */
    public function deposit(
        SavingsAccount $account,
        float $amount,
        string $channel = SavingsTransaction::CHANNEL_BRANCH,
        string $description = '',
        ?string $referenceNumber = null,
        ?int $processedBy = null
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('Deposit amount must be positive.');
        }

        $product = $account->product;

        if ($product->maximum_single_deposit && $amount > (float) $product->maximum_single_deposit) {
            throw new \RuntimeException(
                'Deposit amount exceeds maximum single deposit limit of '.number_format((float) $product->maximum_single_deposit, 2).'.'
            );
        }

        $newBalance = (float) $account->ledger_balance + $amount;

        if ($product->maximum_balance && $newBalance > (float) $product->maximum_balance) {
            throw new \RuntimeException('Deposit would exceed maximum balance allowed by product.');
        }

        $transaction = $this->recordTransaction(
            account: $account,
            type: SavingsTransaction::TYPE_DEPOSIT,
            amount: $amount,
            newBalance: $newBalance,
            channel: $channel,
            description: $description ?: 'Deposit via '.SavingsTransaction::CHANNELS[$channel],
            referenceNumber: $referenceNumber,
            processedBy: $processedBy
        );

        $account->update([
            'ledger_balance' => $newBalance,
            'available_balance' => (float) $account->available_balance + $amount,
            'last_transaction_date' => now()->toDateString(),
        ]);

        return $transaction;
    }

    /**
     * FR-SD-002: Process a withdrawal, enforcing minimum balance.
     *
     * @throws \RuntimeException
     */
    public function withdraw(
        SavingsAccount $account,
        float $amount,
        string $channel = SavingsTransaction::CHANNEL_BRANCH,
        string $description = '',
        ?int $processedBy = null
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new \RuntimeException('Withdrawal amount must be positive.');
        }

        if ($amount > (float) $account->available_balance) {
            throw new \RuntimeException('Insufficient available balance.');
        }

        // FR-SD-002: Minimum balance enforcement
        if ($account->wouldBreachMinimumBalance($amount)) {
            $minimum = number_format((float) $account->product->minimum_balance, 2);
            throw new \RuntimeException("Withdrawal would breach the minimum balance of UGX {$minimum} required by this product.");
        }

        $product = $account->product;

        if ($product->maximum_single_withdrawal && $amount > (float) $product->maximum_single_withdrawal) {
            throw new \RuntimeException('Withdrawal exceeds maximum single withdrawal limit.');
        }

        $newBalance = (float) $account->ledger_balance - $amount;

        $transaction = $this->recordTransaction(
            account: $account,
            type: SavingsTransaction::TYPE_WITHDRAWAL,
            amount: $amount,
            newBalance: $newBalance,
            channel: $channel,
            description: $description ?: 'Withdrawal via '.SavingsTransaction::CHANNELS[$channel],
            processedBy: $processedBy
        );

        $account->update([
            'ledger_balance' => $newBalance,
            'available_balance' => (float) $account->available_balance - $amount,
            'last_transaction_date' => now()->toDateString(),
        ]);

        return $transaction;
    }

    /**
     * FR-SD-015: Process an inter-account or third-party transfer.
     *
     * @throws \RuntimeException
     */
    public function transfer(
        SavingsAccount $fromAccount,
        SavingsAccount $toAccount,
        float $amount,
        string $description = '',
        ?int $processedBy = null
    ): array {
        if ($amount <= 0) {
            throw new \RuntimeException('Transfer amount must be positive.');
        }

        $debit = $this->recordTransaction(
            account: $fromAccount,
            type: SavingsTransaction::TYPE_TRANSFER_OUT,
            amount: $amount,
            newBalance: (float) $fromAccount->ledger_balance - $amount,
            channel: SavingsTransaction::CHANNEL_BRANCH,
            description: $description ?: "Transfer to {$toAccount->account_number}",
            counterpartAccountId: $toAccount->id,
            processedBy: $processedBy
        );

        $credit = $this->recordTransaction(
            account: $toAccount,
            type: SavingsTransaction::TYPE_TRANSFER_IN,
            amount: $amount,
            newBalance: (float) $toAccount->ledger_balance + $amount,
            channel: SavingsTransaction::CHANNEL_BRANCH,
            description: $description ?: "Transfer from {$fromAccount->account_number}",
            counterpartAccountId: $fromAccount->id,
            processedBy: $processedBy
        );

        $fromAccount->update([
            'ledger_balance' => (float) $fromAccount->ledger_balance - $amount,
            'available_balance' => (float) $fromAccount->available_balance - $amount,
            'last_transaction_date' => now()->toDateString(),
        ]);

        $toAccount->update([
            'ledger_balance' => (float) $toAccount->ledger_balance + $amount,
            'available_balance' => (float) $toAccount->available_balance + $amount,
            'last_transaction_date' => now()->toDateString(),
        ]);

        return [$debit, $credit];
    }

    /**
     * FR-SD-014: Validate whether account can be closed (checklist).
     *
     * @return array<int, string> List of blocking reasons
     */
    public function getClosureBlockReasons(SavingsAccount $account): array
    {
        $blocks = [];

        if ((float) $account->held_amount > 0) {
            $blocks[] = 'Account has a hold of UGX '.number_format((float) $account->held_amount).' (e.g., guarantor lock).';
        }

        if ((float) $account->ledger_balance > 0) {
            $blocks[] = 'Account has a remaining balance of UGX '.number_format((float) $account->ledger_balance, 2).'. Please withdraw or transfer before closing.';
        }

        // TODO: When loan module is built, check that no active loans use this account as repayment source
        // if ($account->member->loans()->where('repayment_account_id', $account->id)->where('status', 'active')->exists()) {
        //     $blocks[] = 'Account is linked as repayment source for active loan(s).';
        // }

        return $blocks;
    }

    /**
     * FR-SD-020: Compute interest accrual for a savings account for a given period.
     *
     * @return float Interest amount (rounded to 4 decimal places)
     */
    public function computeInterest(SavingsAccount $account, Carbon $periodStart, Carbon $periodEnd): float
    {
        $product = $account->product;
        $daysInPeriod = $periodStart->diffInDays($periodEnd);

        if ($daysInPeriod <= 0) {
            return 0.0;
        }

        $balance = (float) $account->ledger_balance;
        $annualRate = $product->getApplicableRate($balance) / 100;

        // Daily accrual: Balance × Rate × (Days / 365)
        $accrual = $balance * $annualRate * ($daysInPeriod / 365);

        return round($accrual, 4);
    }

    /**
     * Internal: persist a transaction record and generate a unique ref.
     */
    protected function recordTransaction(
        SavingsAccount $account,
        string $type,
        float $amount,
        float $newBalance,
        string $channel,
        string $description = '',
        ?string $referenceNumber = null,
        ?int $counterpartAccountId = null,
        ?int $processedBy = null
    ): SavingsTransaction {
        return SavingsTransaction::create([
            'transaction_ref' => 'TXN-'.strtoupper(Str::random(10)),
            'account_id' => $account->id,
            'member_id' => $account->member_id,
            'transaction_type' => $type,
            'amount' => $amount,
            'running_balance' => $newBalance,
            'description' => $description,
            'channel' => $channel,
            'reference_number' => $referenceNumber,
            'counterpart_account_id' => $counterpartAccountId,
            'is_reversed' => false,
            'processed_by' => $processedBy,
            'value_date' => now()->toDateString(),
            'posted_at' => now(),
        ]);
    }
}
