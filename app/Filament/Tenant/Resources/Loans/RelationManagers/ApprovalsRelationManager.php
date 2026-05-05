<?php

namespace App\Filament\Tenant\Resources\Loans\RelationManagers;

use App\Models\Tenant\LoanApproval;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $title = 'Approval Chain';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('approval_level')
                    ->label('Level')
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Approver Role')
                    ->badge()
                    ->color('info'),

                TextColumn::make('decision')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'declined' => 'danger',
                        'queried' => 'warning',
                        'deferred' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('amount_approved')
                    ->label('Amount Approved')
                    ->money('UGX'),

                TextColumn::make('notes')
                    ->limit(60)
                    ->placeholder('—'),

                TextColumn::make('decided_at')
                    ->label('Decision At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('approval_level')
            ->reorderable('approval_level')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Approval Record')
                    ->form([
                        TextInput::make('approval_level')->integer()->required()->default(1),
                        TextInput::make('role')->maxLength(60)->required(),
                        Select::make('decision')
                            ->options(LoanApproval::DECISIONS)
                            ->required(),
                        TextInput::make('amount_approved')->label('Amount Approved (UGX)')->numeric(),
                        Textarea::make('notes')->rows(2),
                        DateTimePicker::make('decided_at')->label('Decision Date/Time')->default(now()),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('approval_level')->integer()->required(),
                        TextInput::make('role')->maxLength(60)->required(),
                        Select::make('decision')->options(LoanApproval::DECISIONS)->required(),
                        TextInput::make('amount_approved')->label('Amount Approved (UGX)')->numeric(),
                        Textarea::make('notes')->rows(2),
                        DateTimePicker::make('decided_at')->label('Decision Date/Time'),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
