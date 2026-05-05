<?php

namespace App\Filament\Tenant\Resources\ChartOfAccounts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\ChartOfAccounts\Pages\CreateChartOfAccount;
use App\Filament\Tenant\Resources\ChartOfAccounts\Pages\EditChartOfAccount;
use App\Filament\Tenant\Resources\ChartOfAccounts\Pages\ListChartOfAccounts;
use App\Filament\Tenant\Resources\ChartOfAccounts\Pages\ViewChartOfAccount;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ChartOfAccountResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = ChartOfAccount::class;

    protected static string $moduleKey = 'general_ledger';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'account_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->columns(2)
                    ->schema([
                        TextInput::make('account_code')->required()->unique(ignoreRecord: true)->maxLength(20),
                        TextInput::make('account_name')->required()->maxLength(150),

                        Select::make('account_type')
                            ->options(ChartOfAccount::TYPES)->required(),

                        TextInput::make('account_sub_type')->maxLength(50)->placeholder('e.g. current_asset'),

                        Select::make('parent_id')
                            ->label('Parent Account')
                            ->options(fn () => ChartOfAccount::query()->where('is_header', true)->pluck('account_name', 'id'))
                            ->searchable()->nullable(),

                        Select::make('level')
                            ->options([1 => 'L1 - Category', 2 => 'L2 - Group', 3 => 'L3 - Sub-Group', 4 => 'L4 - Detail', 5 => 'L5 - Leaf'])
                            ->default(4)->required(),

                        Select::make('normal_balance')
                            ->options(['debit' => 'Debit', 'credit' => 'Credit'])
                            ->default('debit')->required(),

                        Textarea::make('description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Flags')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_header')->label('Header Account (no postings)'),
                        Toggle::make('is_bank_account')->label('Bank Account'),
                        Toggle::make('is_cash_account')->label('Cash Account'),
                        Toggle::make('is_system_account')->label('System Account'),
                        Toggle::make('is_active')->label('Active')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_code')->label('Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('account_name')->label('Account Name')->searchable()->sortable(),
                TextColumn::make('account_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'info',
                        'liability' => 'warning',
                        'equity' => 'success',
                        'revenue' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('level')->sortable(),
                TextColumn::make('parent.account_name')->label('Parent')->placeholder('—')->toggleable(),
                TextColumn::make('normal_balance')->label('Normal Bal.')->badge()
                    ->color(fn (string $state): string => $state === 'debit' ? 'info' : 'warning'),
                IconColumn::make('is_header')->label('Header')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('account_code')
            ->filters([
                SelectFilter::make('account_type')->options(ChartOfAccount::TYPES),
                SelectFilter::make('level')->options([1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'L4', 5 => 'L5']),
                TernaryFilter::make('is_header')->label('Headers Only'),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordUrl(fn (ChartOfAccount $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Account')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('account_code')->badge()->color('primary'),
                        TextEntry::make('account_name'),
                        TextEntry::make('account_type')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'asset' => 'info',
                                'liability' => 'warning',
                                'equity', 'revenue' => 'success',
                                'expense' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('account_sub_type')->placeholder('—'),
                        TextEntry::make('parent.account_name')->label('Parent')->placeholder('Root'),
                        TextEntry::make('level'),
                        TextEntry::make('normal_balance'),
                        TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('Flags')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(5)
                    ->schema([
                        IconEntry::make('is_header')->label('Header')->boolean(),
                        IconEntry::make('is_bank_account')->label('Bank')->boolean(),
                        IconEntry::make('is_cash_account')->label('Cash')->boolean(),
                        IconEntry::make('is_system_account')->label('System')->boolean(),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChartOfAccounts::route('/'),
            'create' => CreateChartOfAccount::route('/create'),
            'view' => ViewChartOfAccount::route('/{record}'),
            'edit' => EditChartOfAccount::route('/{record}/edit'),
        ];
    }
}
