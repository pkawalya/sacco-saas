<?php

namespace App\Filament\Tenant\Resources\Loans\Pages;

use App\Filament\Tenant\Resources\Loans\LoanResource;
use App\Filament\Tenant\Resources\Loans\RelationManagers\ApprovalsRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\CollateralRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\GuarantorsRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\RepaymentsRelationManager;
use App\Models\Tenant\Loan;
use App\Services\Tenant\LoanService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disburse')
                ->label('Disburse Loan')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Disbursement')
                ->modalDescription('This will mark the loan as Active and generate the full amortisation schedule. This action cannot be undone.')
                ->form([
                    DatePicker::make('first_repayment_date')
                        ->label('First Repayment Date')
                        ->default(now()->addMonth()->startOfMonth()->toDateString())
                        ->required(),

                    Select::make('disbursement_channel')
                        ->options(['cash' => 'Cash', 'mobile' => 'Mobile Money', 'eft' => 'EFT / Bank Transfer'])
                        ->default('cash')
                        ->required(),
                ])
                ->visible(fn (): bool => $this->record->status === Loan::STATUS_APPROVED)
                ->action(function (array $data): void {
                    /** @var Loan $loan */
                    $loan = $this->record;
                    $product = $loan->product;

                    $firstRepaymentDate = Carbon::parse($data['first_repayment_date']);

                    $schedule = app(LoanService::class)->generateSchedule(
                        principal: (float) $loan->approved_amount,
                        annualRatePercent: (float) $loan->interest_rate,
                        tenureMonths: (int) $loan->tenure_months,
                        firstRepaymentDate: $firstRepaymentDate,
                        method: $loan->interest_method,
                        monthlyMaintenanceFee: $product ? (float) $product->maintenance_fee_monthly : 0.0,
                    );

                    app(LoanService::class)->persistSchedule($loan, $schedule);

                    $maturityDate = $firstRepaymentDate->copy()->addMonths($loan->tenure_months - 1);
                    $monthlyInstalment = $schedule->first()['total_due'] ?? 0;

                    $loan->update([
                        'status' => Loan::STATUS_ACTIVE,
                        'disbursed_amount' => $loan->approved_amount,
                        'outstanding_principal' => $loan->approved_amount,
                        'outstanding_interest' => 0,
                        'outstanding_penalty' => 0,
                        'total_outstanding' => $loan->approved_amount,
                        'monthly_instalment' => $monthlyInstalment,
                        'first_repayment_date' => $firstRepaymentDate->toDateString(),
                        'expected_maturity_date' => $maturityDate->toDateString(),
                        'disbursement_channel' => $data['disbursement_channel'],
                        'disbursed_by' => auth()->id(),
                        'disbursed_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Loan Disbursed')
                        ->body("Loan {$loan->loan_number} is now Active with a {$loan->tenure_months}-month schedule.")
                        ->success()
                        ->send();
                }),

            Action::make('write_off')
                ->label('Write Off')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Write Off Loan?')
                ->modalDescription('This will mark the loan as Written Off. Outstanding balances will remain for reporting purposes.')
                ->visible(fn (): bool => $this->record->status === Loan::STATUS_ACTIVE)
                ->action(function (): void {
                    /** @var Loan $loan */
                    $loan = $this->record;

                    $loan->update(['status' => Loan::STATUS_WRITTEN_OFF]);

                    Notification::make()
                        ->title('Loan Written Off')
                        ->body("Loan {$loan->loan_number} has been written off.")
                        ->warning()
                        ->send();
                }),

            Action::make('restructure')
                ->label('Restructure')
                ->icon('heroicon-o-arrows-right-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restructure Loan')
                ->modalDescription('This marks the loan as Restructured. A new loan record should be created for the restructured terms.')
                ->visible(fn (): bool => $this->record->status === Loan::STATUS_ACTIVE)
                ->action(function (): void {
                    /** @var Loan $loan */
                    $loan = $this->record;

                    $loan->update(['status' => Loan::STATUS_RESTRUCTURED]);

                    Notification::make()
                        ->title('Loan Restructured')
                        ->body("Loan {$loan->loan_number} marked as Restructured.")
                        ->info()
                        ->send();
                }),

            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ApprovalsRelationManager::class,
            GuarantorsRelationManager::class,
            CollateralRelationManager::class,
            RepaymentsRelationManager::class,
        ];
    }
}
