<?php

namespace App\Filament\Tenant\Resources\SavingsAccounts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\SavingsAccounts\Pages\CreateSavingsAccount;
use App\Filament\Tenant\Resources\SavingsAccounts\Pages\EditSavingsAccount;
use App\Filament\Tenant\Resources\SavingsAccounts\Pages\ListSavingsAccounts;
use App\Filament\Tenant\Resources\SavingsAccounts\Pages\ViewSavingsAccount;
use App\Models\Tenant\SavingsAccount;
use App\Models\Tenant\SavingsProduct;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SavingsAccountResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = SavingsAccount::class;

    protected static string $moduleKey = 'savings_deposits';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_TELLER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Savings & Deposits';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'account_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextInput::make('account_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'member_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->member_number}] {$record->full_name}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('product_id')
                            ->label('Savings Product')
                            ->relationship('product', 'product_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('status')
                            ->options(SavingsAccount::STATUSES)
                            ->default(SavingsAccount::STATUS_ACTIVE)
                            ->required(),

                        TextInput::make('branch_code')
                            ->label('Branch Code')
                            ->maxLength(20),

                        DatePicker::make('opened_date')
                            ->label('Opening Date')
                            ->default(now()),

                        Toggle::make('is_joint')
                            ->label('Joint Account')
                            ->reactive(),

                        Select::make('mandate_type')
                            ->label('Mandate Type')
                            ->options([
                                'AOS' => 'Any One to Sign (AOS)',
                                'BAS' => 'Both Any Sign (BAS)',
                            ])
                            ->visible(fn (callable $get): bool => (bool) $get('is_joint')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->label('Account #')
                    ->searchable()
                    ->sortable()

                    ->copyable(),

                TextColumn::make('member.full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'dormant' => 'warning',
                        'suspended' => 'danger',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('ledger_balance')
                    ->label('Ledger Balance')
                    ->money('UGX')
                    ->sortable(),

                TextColumn::make('available_balance')
                    ->label('Available')
                    ->money('UGX')
                    ->sortable(),

                TextColumn::make('held_amount')
                    ->label('On Hold')
                    ->money('UGX')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('branch_code')
                    ->label('Branch')
                    ->toggleable(),

                TextColumn::make('last_transaction_date')
                    ->label('Last Txn')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Opened')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(SavingsAccount::STATUSES),

                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => SavingsProduct::query()->pluck('product_name', 'id')),

                Filter::make('balance_range')
                    ->form([
                        TextInput::make('min_balance')->label('Min Balance (UGX)')->numeric(),
                        TextInput::make('max_balance')->label('Max Balance (UGX)')->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['min_balance']) {
                            $query->where('ledger_balance', '>=', $data['min_balance']);
                        }

                        if ($data['max_balance']) {
                            $query->where('ledger_balance', '<=', $data['max_balance']);
                        }
                    }),
            ])
            ->recordUrl(fn (SavingsAccount $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Account Summary')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('account_number')
                            ->badge()
                            ->color('primary')
                            ->copyable(),

                        TextEntry::make('member.full_name')
                            ->label('Member'),

                        TextEntry::make('product.product_name')
                            ->label('Product'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'dormant' => 'warning',
                                'suspended' => 'danger',
                                'closed' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Balances')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('ledger_balance')
                            ->label('Ledger Balance')
                            ->money('UGX')
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('available_balance')
                            ->label('Available Balance')
                            ->money('UGX')
                            ->color('success')
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('held_amount')
                            ->label('On Hold')
                            ->money('UGX')
                            ->color('warning'),

                        TextEntry::make('accrued_interest')
                            ->label('Accrued Interest')
                            ->money('UGX')
                            ->color('info'),
                    ]),

                Section::make('Account Metadata')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('branch_code')
                            ->label('Branch')
                            ->placeholder('—'),

                        TextEntry::make('opened_date')
                            ->date(),

                        TextEntry::make('last_transaction_date')
                            ->label('Last Transaction')
                            ->date()
                            ->placeholder('No transactions'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavingsAccounts::route('/'),
            'create' => CreateSavingsAccount::route('/create'),
            'view' => ViewSavingsAccount::route('/{record}'),
            'edit' => EditSavingsAccount::route('/{record}/edit'),
        ];
    }
}
