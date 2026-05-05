<?php

namespace App\Filament\Tenant\Resources\Loans\RelationManagers;

use App\Models\Tenant\Member;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuarantorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guarantors';

    protected static ?string $title = 'Guarantors';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guarantorMember.full_name')
                    ->label('Guarantor')
                    ->searchable(),

                TextColumn::make('guaranteed_amount')
                    ->label('Guaranteed Amount')
                    ->money('UGX'),

                TextColumn::make('locked_amount')
                    ->label('Locked Amount')
                    ->money('UGX')
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'released' => 'gray',
                        'pending' => 'info',
                        default => 'warning',
                    }),

                TextColumn::make('released_date')
                    ->label('Released On')
                    ->date()
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Guarantor')
                    ->form([
                        Select::make('guarantor_member_id')
                            ->label('Guarantor Member')
                            ->options(
                                fn () => Member::query()
                                    ->active()
                                    ->get()
                                    ->mapWithKeys(fn (Member $m) => [$m->id => "[{$m->member_number}] {$m->full_name}"])
                            )
                            ->searchable()
                            ->required(),

                        TextInput::make('guaranteed_amount')
                            ->label('Guaranteed Amount (UGX)')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }
}
