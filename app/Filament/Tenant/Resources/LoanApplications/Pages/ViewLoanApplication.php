<?php

namespace App\Filament\Tenant\Resources\LoanApplications\Pages;

use App\Filament\Tenant\Resources\LoanApplications\LoanApplicationResource;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanApplication;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewLoanApplication extends ViewRecord
{
    protected static string $resource = LoanApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Submit Application?')
                ->modalDescription('The application will be sent for credit review. Ensure all details are complete.')
                ->visible(fn (): bool => $this->record->status === LoanApplication::STATUS_DRAFT)
                ->action(function (): void {
                    /** @var LoanApplication $application */
                    $application = $this->record;

                    $application->update([
                        'status' => LoanApplication::STATUS_SUBMITTED,
                        'submitted_by' => auth()->id(),
                        'submitted_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Application Submitted')
                        ->body("Application {$application->application_ref} submitted for review.")
                        ->success()
                        ->send();
                }),

            Action::make('mark_under_review')
                ->label('Mark Under Review')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status === LoanApplication::STATUS_SUBMITTED)
                ->action(function (): void {
                    /** @var LoanApplication $application */
                    $application = $this->record;

                    $application->update(['status' => LoanApplication::STATUS_UNDER_REVIEW]);

                    Notification::make()
                        ->title('Under Review')
                        ->body("Application {$application->application_ref} is now under credit review.")
                        ->warning()
                        ->send();
                }),

            Action::make('approve')
                ->label('Approve & Create Loan')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Application')
                ->modalDescription('Approving will create a Loan record in the system ready for disbursement.')
                ->form([
                    TextInput::make('amount_approved')
                        ->label('Approved Amount (UGX)')
                        ->numeric()
                        ->required()
                        ->default(fn (): float => (float) ($this->record->amount_recommended ?? $this->record->amount_requested)),

                    TextInput::make('tenure_approved')
                        ->label('Approved Tenure (months)')
                        ->integer()
                        ->required()
                        ->default(fn (): int => (int) ($this->record->tenure_months_recommended ?? $this->record->tenure_months_requested)),

                    Textarea::make('approval_notes')
                        ->label('Approval Notes')
                        ->rows(2),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [
                    LoanApplication::STATUS_SUBMITTED,
                    LoanApplication::STATUS_UNDER_REVIEW,
                ]))
                ->action(function (array $data): void {
                    /** @var LoanApplication $application */
                    $application = $this->record;

                    // Use approved amounts, falling back to requested
                    $approvedAmount = (float) $data['amount_approved'];
                    $tenureMonths = (int) $data['tenure_approved'];

                    // Look up interest rate from linked product
                    $interestRate = $application->product
                        ? (float) $application->product->interest_rate
                        : 0.0;

                    $interestMethod = $application->product
                        ? $application->product->interest_method
                        : 'reducing';

                    // Build a loan number: LN-YYYYMM-RANDOM
                    $loanNumber = 'LN-'.now()->format('Ym').'-'.strtoupper(Str::random(6));

                    $loan = Loan::create([
                        'loan_number' => $loanNumber,
                        'member_id' => $application->member_id,
                        'product_id' => $application->product_id,
                        'application_id' => $application->id,
                        'principal_amount' => $approvedAmount,
                        'approved_amount' => $approvedAmount,
                        'tenure_months' => $tenureMonths,
                        'interest_rate' => $interestRate,
                        'interest_method' => $interestMethod,
                        'outstanding_principal' => $approvedAmount,
                        'outstanding_interest' => 0,
                        'outstanding_penalty' => 0,
                        'total_outstanding' => $approvedAmount,
                        'branch_code' => $application->branch_code,
                        'loan_officer_id' => auth()->id(),
                        'authorised_by' => auth()->id(),
                        'status' => Loan::STATUS_APPROVED,
                    ]);

                    $application->update([
                        'status' => LoanApplication::STATUS_APPROVED,
                        'amount_recommended' => $approvedAmount,
                        'tenure_months_recommended' => $tenureMonths,
                        'officer_notes' => $data['approval_notes'] ?? $application->officer_notes,
                        'decision_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Application Approved')
                        ->body("Loan {$loan->loan_number} created and is ready for disbursement.")
                        ->success()
                        ->send();
                }),

            Action::make('decline')
                ->label('Decline')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Decline Application')
                ->form([
                    Textarea::make('decline_reason')
                        ->label('Reason for Decline')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [
                    LoanApplication::STATUS_SUBMITTED,
                    LoanApplication::STATUS_UNDER_REVIEW,
                ]))
                ->action(function (array $data): void {
                    /** @var LoanApplication $application */
                    $application = $this->record;

                    $application->update([
                        'status' => LoanApplication::STATUS_DECLINED,
                        'officer_notes' => $data['decline_reason'],
                        'decision_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Application Declined')
                        ->body("Application {$application->application_ref} has been declined.")
                        ->danger()
                        ->send();
                }),

            Action::make('withdraw')
                ->label('Withdraw')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [
                    LoanApplication::STATUS_DRAFT,
                    LoanApplication::STATUS_SUBMITTED,
                ]))
                ->action(function (): void {
                    /** @var LoanApplication $application */
                    $application = $this->record;

                    $application->update([
                        'status' => LoanApplication::STATUS_WITHDRAWN,
                        'decision_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Application Withdrawn')
                        ->body("Application {$application->application_ref} has been withdrawn.")
                        ->info()
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}
