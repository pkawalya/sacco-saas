<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stage')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'info',
                        2 => 'warning',
                        3 => 'success',
                        4 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => 'Stage '.$state)
                    ->sortable(),
                TextColumn::make('modules')
                    ->label('Modules')
                    ->formatStateUsing(function (mixed $state): string {
                        $modules = is_array($state) ? $state : json_decode((string) $state, true);

                        return is_array($modules) ? count($modules).' modules' : '—';
                    })
                    ->color('primary'),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_cycle')
                    ->searchable(),
                TextColumn::make('duration_months')
                    ->label('Duration')
                    ->suffix(' months')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_custom')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tenants_count')
                    ->counts('tenants')
                    ->label('Tenants')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->options([
                        1 => 'Stage 1 — SACCO',
                        2 => 'Stage 2 — Regulated',
                        3 => 'Stage 3 — MFI',
                        4 => 'Stage 4 — MFB',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
