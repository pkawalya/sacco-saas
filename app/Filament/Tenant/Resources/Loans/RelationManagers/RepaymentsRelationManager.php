<?php

namespace App\Filament\Tenant\Resources\Loans\RelationManagers;

use App\Models\Tenant\LoanRepayment;
use App\Services\Tenant\LoanService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'repayments';

    protected static ?string $title = 'Repayment History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('UGX')
                    ->sortable(),

                TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'branch' => 'success',
                        'mobile', 'ussd' => 'info',
                        'agent' => 'warning',
                        'payroll', 'standing_order' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('allocated_to_principal')
                    ->label('→ Principal')
                    ->money('UGX')
                    ->toggleable(),

                TextColumn::make('allocated_to_interest')
                    ->label('→ Interest')
                    ->money('UGX')
                    ->toggleable(),

                TextColumn::make('allocated_to_penalty')
                    ->label('→ Penalty')
                    ->money('UGX')
                    ->toggleable(),

                TextColumn::make('outstanding_after')
                    ->label('Balance After')
                    ->money('UGX')
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'success'),

                TextColumn::make('is_reversed')
                    ->label('Reversed')
                    ->formatStateUsing(fn (bool $state): string => $state ? '✗ Yes' : '—')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),

                TextColumn::make('posted_at')
                    ->label('Posted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('posted_at', 'desc')
            ->headerActions([
                Action::make('post_repayment')
                    ->label('Post Repayment')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('success')
                    ->form([
                        TextInput::make('amount_paid')
                            ->label('Amount Paid (UGX)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Select::make('channel')
                            ->options(LoanRepayment::CHANNELS)
                            ->default(LoanRepayment::CHANNEL_BRANCH)
                            ->required(),

                        TextInput::make('reference_number')
                            ->label('Reference / Receipt No.')
                            ->maxLength(60),

                        DatePicker::make('value_date')
                            ->label('Value Date')
                            ->default(now()->toDateString()),
                    ])
                    ->action(function (array $data): void {
                        $loan = $this->getOwnerRecord();

                        if (! in_array($loan->status, ['active', 'approved'])) {
                            Notification::make()
                                ->title('Cannot post repayment')
                                ->body("Loan is not active or approved (status: {$loan->status}).")
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            app(LoanService::class)->processRepayment(
                                loan: $loan,
                                amountPaid: (float) $data['amount_paid'],
                                channel: $data['channel'],
                                referenceNumber: $data['reference_number'] ?? null,
                                processedBy: auth()->id(),
                            );

                            Notification::make()
                                ->title('Repayment posted successfully')
                                ->body('UGX '.number_format((float) $data['amount_paid'], 2).' applied to loan '.$loan->loan_number.'.')
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Repayment failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
