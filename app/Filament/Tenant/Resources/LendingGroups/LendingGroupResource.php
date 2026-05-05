<?php

namespace App\Filament\Tenant\Resources\LendingGroups;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\LendingGroups\Pages\ListLendingGroups;
use App\Filament\Tenant\Resources\LendingGroups\Pages\ViewLendingGroup;
use App\Models\Tenant\LendingGroup;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LendingGroupResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = LendingGroup::class;

    protected static string $moduleKey = 'advanced_analytics';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Group Lending';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group_code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('group_name')->searchable()->sortable(),
                TextColumn::make('liability_type')->badge(),
                TextColumn::make('cycle_number')->label('Cycle')->badge()->color('info'),
                TextColumn::make('repayment_rate')->suffix('%')
                    ->color(fn (string $state): string => (float) $state >= 90 ? 'success' : ((float) $state >= 70 ? 'warning' : 'danger')),
                TextColumn::make('group_savings_balance')->money('UGX'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'probation' => 'warning',
                        'suspended' => 'danger', 'graduated' => 'info', default => 'gray',
                    }),
            ])
            ->defaultSort('group_name')
            ->filters([
                SelectFilter::make('status')->options(LendingGroup::STATUSES),
            ])
            ->recordUrl(fn (LendingGroup $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Group')
                ->icon(Heroicon::OutlinedUserGroup)
                ->columns(3)
                ->schema([
                    TextEntry::make('group_code')->badge()->color('primary')->copyable(),
                    TextEntry::make('group_name'),
                    TextEntry::make('branch_code')->placeholder('—'),
                    TextEntry::make('liability_type')->badge(),
                    TextEntry::make('cycle_number')->badge(),
                    TextEntry::make('repayment_rate')->suffix('%'),
                    TextEntry::make('max_members'),
                    TextEntry::make('min_members'),
                    TextEntry::make('max_loan_per_member')->money('UGX'),
                    TextEntry::make('group_savings_balance')->money('UGX'),
                    TextEntry::make('status')->badge(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLendingGroups::route('/'),
            'view' => ViewLendingGroup::route('/{record}'),
        ];
    }
}
