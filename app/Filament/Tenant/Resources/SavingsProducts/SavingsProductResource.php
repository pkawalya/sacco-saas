<?php

namespace App\Filament\Tenant\Resources\SavingsProducts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\SavingsProducts\Pages\CreateSavingsProduct;
use App\Filament\Tenant\Resources\SavingsProducts\Pages\EditSavingsProduct;
use App\Filament\Tenant\Resources\SavingsProducts\Pages\ListSavingsProducts;
use App\Filament\Tenant\Resources\SavingsProducts\Pages\ViewSavingsProduct;
use App\Models\Tenant\SavingsProduct;
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

class SavingsProductResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = SavingsProduct::class;

    protected static string $moduleKey = 'savings_deposits';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Savings & Deposits';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'product_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Identity')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextInput::make('product_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g. SAV-REG'),

                        TextInput::make('product_name')
                            ->required()
                            ->maxLength(150),

                        Select::make('product_type')
                            ->options(SavingsProduct::TYPES)
                            ->required()
                            ->default(SavingsProduct::TYPE_REGULAR),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Interest & Computation')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(3)
                    ->schema([
                        TextInput::make('interest_rate')
                            ->label('Base Interest Rate (%)')
                            ->numeric()
                            ->step(0.0001)
                            ->default(0)
                            ->suffix('%'),

                        Select::make('interest_computation')
                            ->label('Computation Method')
                            ->options(SavingsProduct::COMPUTATIONS)
                            ->default(SavingsProduct::COMPUTATION_DAILY_AVERAGE)
                            ->required(),

                        Select::make('interest_posting_cycle')
                            ->label('Posting Cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'annually' => 'Annually',
                            ])
                            ->default('monthly')
                            ->required(),

                        Toggle::make('has_tiered_rates')
                            ->label('Enable Tiered Rates')
                            ->reactive(),
                    ]),

                Section::make('Balance Limits')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(3)
                    ->schema([
                        TextInput::make('minimum_balance')
                            ->label('Minimum Balance (UGX)')
                            ->numeric()
                            ->default(0),

                        TextInput::make('maximum_balance')
                            ->label('Maximum Balance (UGX)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('minimum_opening_deposit')
                            ->label('Minimum Opening Deposit (UGX)')
                            ->numeric()
                            ->default(0),

                        TextInput::make('maximum_single_deposit')
                            ->label('Max Single Deposit (UGX)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('maximum_single_withdrawal')
                            ->label('Max Single Withdrawal (UGX)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('free_withdrawals_per_month')
                            ->label('Free Withdrawals / Month')
                            ->integer()
                            ->default(0),
                    ]),

                Section::make('Penalties')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->columns(2)
                    ->schema([
                        TextInput::make('below_minimum_penalty')
                            ->label('Below Minimum Penalty / Month (UGX)')
                            ->numeric()
                            ->default(0),

                        TextInput::make('early_withdrawal_penalty_rate')
                            ->label('Early Withdrawal Penalty Rate (%)')
                            ->numeric()
                            ->step(0.0001)
                            ->default(0)
                            ->suffix('%')
                            ->helperText('For fixed deposits only.'),
                    ]),

                Section::make('Fixed Deposit Settings')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(3)
                    ->schema([
                        TextInput::make('minimum_tenure_months')
                            ->label('Min Tenure (months)')
                            ->integer()
                            ->nullable(),

                        TextInput::make('maximum_tenure_months')
                            ->label('Max Tenure (months)')
                            ->integer()
                            ->nullable(),

                        Toggle::make('auto_rollover')
                            ->label('Auto Rollover on Maturity'),
                    ])
                    ->visible(fn (callable $get): bool => $get('product_type') === SavingsProduct::TYPE_FIXED_DEPOSIT),

                Section::make('Settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Product Active')
                            ->default(true),

                        Toggle::make('is_joint_allowed')
                            ->label('Allow Joint Accounts'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()

                    ->badge()
                    ->color('primary'),

                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SavingsProduct::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'fixed_deposit' => 'warning',
                        'regular' => 'success',
                        default => 'info',
                    }),

                TextColumn::make('interest_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('minimum_balance')
                    ->label('Min Balance')
                    ->money('UGX')
                    ->sortable(),

                TextColumn::make('accounts_count')
                    ->counts('accounts')
                    ->label('Accounts')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_type')
                    ->options(SavingsProduct::TYPES),

                TernaryFilter::make('is_active')
                    ->label('Active Products'),
            ])
            ->recordUrl(fn (SavingsProduct $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Product Details')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product_code')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('product_name'),

                        TextEntry::make('product_type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => SavingsProduct::TYPES[$state] ?? $state),

                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Interest')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('interest_rate')
                            ->label('Annual Rate')
                            ->suffix('%'),

                        TextEntry::make('interest_computation')
                            ->label('Computation Method')
                            ->formatStateUsing(fn (string $state): string => SavingsProduct::COMPUTATIONS[$state] ?? $state),

                        TextEntry::make('interest_posting_cycle')
                            ->label('Posting Cycle'),

                        IconEntry::make('has_tiered_rates')
                            ->label('Tiered Rates')
                            ->boolean(),
                    ]),

                Section::make('Limits')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('minimum_balance')
                            ->money('UGX'),

                        TextEntry::make('maximum_balance')
                            ->money('UGX')
                            ->placeholder('No limit'),

                        TextEntry::make('minimum_opening_deposit')
                            ->money('UGX'),

                        TextEntry::make('maximum_single_deposit')
                            ->money('UGX')
                            ->placeholder('No limit'),

                        TextEntry::make('maximum_single_withdrawal')
                            ->money('UGX')
                            ->placeholder('No limit'),

                        TextEntry::make('free_withdrawals_per_month')
                            ->label('Free Withdrawals/Month'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavingsProducts::route('/'),
            'create' => CreateSavingsProduct::route('/create'),
            'view' => ViewSavingsProduct::route('/{record}'),
            'edit' => EditSavingsProduct::route('/{record}/edit'),
        ];
    }
}
