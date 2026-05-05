<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Core double-entry accounting service.
 *
 * Covers FR-GL-001 (double-entry enforcement), FR-GL-002 (journal types),
 * FR-GL-003 (period controls), FR-GL-004 (trial balance).
 */
class GeneralLedgerService
{
    /**
     * FR-GL-001: Create and post a journal entry with double-entry enforcement.
     *
     * @param  array<int, array{account_id: int, debit: float, credit: float, narration?: string, cost_centre_code?: string, branch_code?: string}>  $lines
     *
     * @throws \RuntimeException
     */
    public function createJournalEntry(
        string $description,
        string $transactionDate,
        array $lines,
        string $journalType = JournalEntry::TYPE_SYSTEM,
        ?string $sourceModule = null,
        ?string $sourceReference = null,
        ?int $sourceId = null,
        ?int $createdBy = null,
        ?string $autoReverseDate = null,
    ): JournalEntry {
        // ─── Validation ─────────────────────────────────

        if (count($lines) < 2) {
            throw new \RuntimeException('A journal entry requires at least two lines.');
        }

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \RuntimeException(
                "Journal entry is unbalanced: debits ({$totalDebit}) ≠ credits ({$totalCredit})."
            );
        }

        // Validate all accounts are postable (not header, not inactive)
        $accountIds = array_column($lines, 'account_id');
        $postableCount = ChartOfAccount::query()
            ->whereIn('id', $accountIds)
            ->where('is_header', false)
            ->where('is_active', true)
            ->count();

        if ($postableCount !== count(array_unique($accountIds))) {
            throw new \RuntimeException('One or more accounts are either headers, inactive, or do not exist.');
        }

        // Validate period is open
        $period = $this->resolvePeriod($transactionDate);

        if ($period && ! $period->isOpen()) {
            throw new \RuntimeException(
                "Accounting period '{$period->period_name}' is closed. Cannot post journal entries."
            );
        }

        // ─── Create ─────────────────────────────────────

        return DB::transaction(function () use ($description, $transactionDate, $lines, $journalType, $sourceModule, $sourceReference, $sourceId, $createdBy, $autoReverseDate, $totalDebit, $totalCredit, $period) {
            $entry = JournalEntry::create([
                'journal_number' => $this->generateJournalNumber($journalType),
                'journal_type' => $journalType,
                'transaction_date' => $transactionDate,
                'value_date' => $transactionDate,
                'description' => $description,
                'source_module' => $sourceModule,
                'source_reference' => $sourceReference,
                'source_id' => $sourceId,
                'period_id' => $period?->id,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'auto_reverse_date' => $autoReverseDate,
                'status' => ($journalType === JournalEntry::TYPE_MANUAL)
                    ? JournalEntry::STATUS_DRAFT
                    : JournalEntry::STATUS_POSTED,
                'created_by' => $createdBy,
                'posted_at' => ($journalType === JournalEntry::TYPE_MANUAL) ? null : now(),
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'narration' => $line['narration'] ?? null,
                    'cost_centre_code' => $line['cost_centre_code'] ?? null,
                    'branch_code' => $line['branch_code'] ?? null,
                    'source_reference' => $sourceReference,
                ]);
            }

            return $entry;
        });
    }

    /**
     * FR-GL-002: Approve and post a manual journal entry (dual auth).
     *
     * @throws \RuntimeException
     */
    public function approveManualJournal(JournalEntry $entry, int $approverId): void
    {
        if ($entry->journal_type !== JournalEntry::TYPE_MANUAL) {
            throw new \RuntimeException('Only manual journal entries require approval.');
        }

        if ($entry->status !== JournalEntry::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft journal entries can be approved.');
        }

        if ($entry->created_by === $approverId) {
            throw new \RuntimeException('The creator cannot approve their own journal entry (four-eyes principle).');
        }

        $entry->update([
            'status' => JournalEntry::STATUS_POSTED,
            'approved_by' => $approverId,
            'posted_at' => now(),
        ]);
    }

    /**
     * FR-GL-002: Reverse a posted journal entry by creating a mirror entry.
     *
     * @throws \RuntimeException
     */
    public function reverseJournalEntry(JournalEntry $entry, int $userId, string $reason = 'Reversal'): JournalEntry
    {
        if ($entry->status !== JournalEntry::STATUS_POSTED) {
            throw new \RuntimeException('Only posted journal entries can be reversed.');
        }

        $reversalLines = [];

        foreach ($entry->lines as $line) {
            $reversalLines[] = [
                'account_id' => $line->account_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'narration' => 'Reversal: '.($line->narration ?? ''),
                'cost_centre_code' => $line->cost_centre_code,
                'branch_code' => $line->branch_code,
            ];
        }

        $reversal = $this->createJournalEntry(
            description: "Reversal of {$entry->journal_number}: {$reason}",
            transactionDate: now()->toDateString(),
            lines: $reversalLines,
            journalType: JournalEntry::TYPE_SYSTEM,
            sourceModule: $entry->source_module,
            sourceReference: $entry->journal_number,
            createdBy: $userId,
        );

        $reversal->update([
            'is_reversal' => true,
            'reversal_of_id' => $entry->id,
        ]);

        $entry->update(['status' => JournalEntry::STATUS_REVERSED]);

        return $reversal;
    }

    /**
     * FR-GL-004: Compute trial balance for a given period or date range.
     *
     * @return Collection<int, array{account_id: int, account_code: string, account_name: string, account_type: string, total_debit: float, total_credit: float, balance: float}>
     */
    public function computeTrialBalance(?int $periodId = null, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = JournalLine::query()
            ->join('chart_of_accounts', 'journal_lines.account_id', '=', 'chart_of_accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->select([
                'chart_of_accounts.id as account_id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            ])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
            );

        if ($periodId) {
            $query->where('journal_entries.period_id', $periodId);
        }

        if ($fromDate) {
            $query->where('journal_entries.transaction_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('journal_entries.transaction_date', '<=', $toDate);
        }

        return $query->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $debit = (float) $row->total_debit;
                $credit = (float) $row->total_credit;

                return [
                    'account_id' => $row->account_id,
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'account_type' => $row->account_type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance' => ($row->normal_balance === 'debit')
                        ? round($debit - $credit, 2)
                        : round($credit - $debit, 2),
                ];
            });
    }

    /**
     * Get the account balance for a specific GL account across all posted entries.
     */
    public function getAccountBalance(int $accountId, ?string $asOfDate = null): float
    {
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->where('journal_lines.account_id', $accountId);

        if ($asOfDate) {
            $query->where('journal_entries.transaction_date', '<=', $asOfDate);
        }

        $totals = $query->select([
            DB::raw('SUM(journal_lines.debit) as total_debit'),
            DB::raw('SUM(journal_lines.credit) as total_credit'),
        ])->first();

        $account = ChartOfAccount::find($accountId);
        $debit = (float) ($totals->total_debit ?? 0);
        $credit = (float) ($totals->total_credit ?? 0);

        return $account && $account->isDebitNormal()
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }

    // ─── Private Helpers ─────────────────────────────────

    /**
     * Generate a sequential journal number based on type.
     */
    protected function generateJournalNumber(string $type): string
    {
        $prefix = match ($type) {
            JournalEntry::TYPE_SYSTEM => 'SYS',
            JournalEntry::TYPE_MANUAL => 'MNL',
            JournalEntry::TYPE_AUTO_REVERSAL => 'ARV',
            default => 'JRN',
        };

        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    /**
     * Resolve the accounting period for a transaction date.
     */
    protected function resolvePeriod(string $transactionDate): ?AccountingPeriod
    {
        $date = Carbon::parse($transactionDate);

        return AccountingPeriod::query()
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->first();
    }
}
