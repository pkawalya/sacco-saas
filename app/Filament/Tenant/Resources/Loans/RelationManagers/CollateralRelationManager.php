<?php

namespace App\Filament\Tenant\Resources\Loans\RelationManagers;

use App\Models\Tenant\LoanCollateral;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollateralRelationManager extends RelationManager
{
    protected static string $relationship = 'collateral';

    protected static ?string $title = 'Collateral';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_type')
                    ->label('Asset Type')
                    ->formatStateUsing(fn (string $state): string => LoanCollateral::TYPES[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                TextColumn::make('asset_description')
                    ->label('Description')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('estimated_value')
                    ->label('Est. Value')
                    ->money('UGX'),

                TextColumn::make('forced_sale_value')
                    ->label('FSV')
                    ->money('UGX')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valuation_date')
                    ->label('Valued On')
                    ->date()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'released' => 'gray',
                        'foreclosed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Collateral')
                    ->form([
                        Select::make('asset_type')
                            ->options(LoanCollateral::TYPES)
                            ->required(),

                        TextInput::make('asset_description')
                            ->label('Description')
                            ->maxLength(255),

                        TextInput::make('asset_identifier')
                            ->label('Identifier (e.g. Title No., Reg. Plate)')
                            ->maxLength(100),

                        TextInput::make('location')
                            ->maxLength(255),

                        TextInput::make('estimated_value')
                            ->label('Estimated Value (UGX)')
                            ->numeric()
                            ->required(),

                        TextInput::make('forced_sale_value')
                            ->label('Forced Sale Value (UGX)')
                            ->numeric(),

                        DatePicker::make('valuation_date')
                            ->label('Valuation Date'),

                        TextInput::make('valuer_name')
                            ->maxLength(120),

                        Select::make('status')
                            ->options([
                                LoanCollateral::STATUS_ACTIVE => 'Active',
                                LoanCollateral::STATUS_RELEASED => 'Released',
                                LoanCollateral::STATUS_FORECLOSED => 'Foreclosed',
                            ])
                            ->default(LoanCollateral::STATUS_ACTIVE),

                        Toggle::make('is_insured')
                            ->label('Is Insured?'),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Select::make('asset_type')->options(LoanCollateral::TYPES)->required(),
                        TextInput::make('asset_description')->label('Description')->maxLength(255),
                        TextInput::make('estimated_value')->numeric()->required(),
                        TextInput::make('forced_sale_value')->numeric(),
                        DatePicker::make('valuation_date'),
                        Select::make('status')->options([
                            LoanCollateral::STATUS_ACTIVE => 'Active',
                            LoanCollateral::STATUS_RELEASED => 'Released',
                            LoanCollateral::STATUS_FORECLOSED => 'Foreclosed',
                        ]),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
