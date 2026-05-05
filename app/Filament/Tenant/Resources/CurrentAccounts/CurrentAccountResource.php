<?php

namespace App\Filament\Tenant\Resources\CurrentAccounts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\CurrentAccounts\Pages\ListCurrentAccounts;
use App\Filament\Tenant\Resources\CurrentAccounts\Pages\ViewCurrentAccount;
use App\Models\Tenant\CurrentAccount;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CurrentAccountResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = CurrentAccount::class;

    protected static string $moduleKey = 'mfb_upgrade';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_TELLER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'MFB Accounts';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('account_holder')->searchable()->sortable(),
                TextColumn::make('account_type')->badge(),
                TextColumn::make('ledger_balance')->money('UGX'),
                TextColumn::make('overdraft_limit')->money('UGX'),
                IconColumn::make('deposit_insured')->boolean(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'dormant' => 'warning', 'frozen' => 'danger', default => 'gray',
                    }),
            ])
            ->defaultSort('account_holder')
            ->filters([
                SelectFilter::make('status')->options(CurrentAccount::STATUSES),
                SelectFilter::make('account_type')->options(CurrentAccount::TYPES),
            ])
            ->recordUrl(fn (CurrentAccount $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Account Details')
                ->icon(Heroicon::OutlinedBanknotes)
                ->columns(3)
                ->schema([
                    TextEntry::make('account_number')->badge()->color('primary')->copyable(),
                    TextEntry::make('account_holder'),
                    TextEntry::make('account_type')->badge(),
                    TextEntry::make('ledger_balance')->money('UGX'),
                    TextEntry::make('available_balance')->money('UGX'),
                    TextEntry::make('overdraft_limit')->money('UGX'),
                    TextEntry::make('minimum_balance')->money('UGX'),
                    TextEntry::make('monthly_fee')->money('UGX'),
                    TextEntry::make('insured_amount')->money('UGX'),
                    IconEntry::make('cheque_book_issued')->boolean(),
                    IconEntry::make('debit_card_linked')->boolean(),
                    IconEntry::make('deposit_insured')->boolean(),
                    TextEntry::make('status')->badge(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrentAccounts::route('/'),
            'view' => ViewCurrentAccount::route('/{record}'),
        ];
    }
}
