<?php

namespace App\Filament\Tenant\Resources\Members\Tables;

use App\Filament\Tenant\Resources\Members\MemberResource;
use App\Models\Tenant\Member;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // ─── FROZEN IDENTITY COLUMNS ─────────
                TextColumn::make('member_number')
                    ->label('Member #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(['first_name']),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'applicant' => 'warning',
                        'dormant' => 'gray',
                        'suspended' => 'danger',
                        'deceased' => 'gray',
                        'exited' => 'info',
                        default => 'gray',
                    }),

                // ─── SCROLLABLE DATA COLUMNS ─────────
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('national_id_number')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('primary_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('member_category')
                    ->label('Category')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('branch_code')
                    ->label('Branch')
                    ->toggleable(),

                TextColumn::make('kyc_score')
                    ->label('KYC %')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state, Member $record): string => $state >= $record->kyc_threshold ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('shares.total_value')
                    ->label('Shares')
                    ->money('UGX')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('district')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('referral_source')
                    ->label('Referral')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(Member::STATUSES)->mapWithKeys(
                        fn (string $s): array => [$s => ucfirst($s)]
                    )->toArray())
                    ->multiple(),

                SelectFilter::make('member_category')
                    ->label('Category')
                    ->options([
                        'individual' => 'Individual',
                        'group' => 'Group Member',
                        'corporate' => 'Corporate',
                        'staff' => 'Staff',
                    ]),

                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),

                Filter::make('kyc_incomplete')
                    ->label('KYC Incomplete')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('kyc_score', '<', 'kyc_threshold'))
                    ->toggle(),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('Registered From'),
                        DatePicker::make('until')
                            ->label('Registered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordUrl(fn (Member $record): string => MemberResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
