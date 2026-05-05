<?php

namespace App\Filament\Tenant\Resources\FixedDeposits;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\FixedDeposits\Pages\CreateFixedDeposit;
use App\Filament\Tenant\Resources\FixedDeposits\Pages\EditFixedDeposit;
use App\Filament\Tenant\Resources\FixedDeposits\Pages\ListFixedDeposits;
use App\Filament\Tenant\Resources\FixedDeposits\Pages\ViewFixedDeposit;
use App\Models\Tenant\FixedDeposit;
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

class FixedDepositResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = FixedDeposit::class;

    protected static string $moduleKey = 'savings_deposits';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_TELLER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|\UnitEnum|null $navigationGroup = 'Savings & Deposits';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'fd_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fixed Deposit Details')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(2)
                    ->schema([
                        TextInput::make('fd_number')
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
                            ->label('FD Product')
                            ->relationship('product', 'product_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('principal_amount')
                            ->label('Principal Amount (UGX)')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        TextInput::make('interest_rate')
                            ->label('Interest Rate (%)')
                            ->numeric()
                            ->step(0.0001)
                            ->required()
                            ->suffix('%'),

                        TextInput::make('tenure_months')
                            ->label('Tenure (months)')
                            ->integer()
                            ->required()
                            ->minValue(1),

                        DatePicker::make('start_date')
                            ->required()
                            ->default(now()),

                        DatePicker::make('maturity_date')
                            ->required(),

                        Toggle::make('auto_rollover')
                            ->label('Auto Rollover on Maturity')
                            ->reactive(),

                        Select::make('rollover_type')
                            ->label('Rollover Type')
                            ->options([
                                FixedDeposit::ROLLOVER_PRINCIPAL_ONLY => 'Principal Only',
                                FixedDeposit::ROLLOVER_PRINCIPAL_AND_INTEREST => 'Principal + Interest',
                            ])
                            ->visible(fn (callable $get): bool => (bool) $get('auto_rollover')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fd_number')
                    ->label('FD #')
                    ->searchable()
                    ->sortable()

                    ->copyable(),

                TextColumn::make('member.full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('UGX')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'matured' => 'warning',
                        'rolled_over' => 'info',
                        'terminated' => 'gray',
                        'broken' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('interest_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('maturity_date')
                    ->label('Matures')
                    ->date()
                    ->sortable()
                    ->color(fn ($state, FixedDeposit $record): string => $record->isDueForMaturity() ? 'warning' : 'gray'),

                TextColumn::make('maturity_amount')
                    ->label('At Maturity')
                    ->money('UGX')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tenure_months')
                    ->label('Months')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('maturity_date', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->options(FixedDeposit::STATUSES),

                Filter::make('maturing_soon')
                    ->label('Maturing in 30 Days')
                    ->query(fn ($query) => $query->where('status', 'active')->whereBetween('maturity_date', [now(), now()->addDays(30)])),
            ])
            ->recordUrl(fn (FixedDeposit $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Fixed Deposit Summary')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('fd_number')
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
                                'matured' => 'warning',
                                'rolled_over' => 'info',
                                'terminated' => 'gray',
                                'broken' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Financial Details')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('principal_amount')
                            ->label('Principal')
                            ->money('UGX')
                            ->size(TextEntry\TextEntrySize::Large),

                        TextEntry::make('interest_rate')
                            ->label('Interest Rate')
                            ->suffix('% p.a.'),

                        TextEntry::make('interest_earned')
                            ->label('Interest Earned')
                            ->money('UGX'),

                        TextEntry::make('maturity_amount')
                            ->label('Maturity Amount')
                            ->money('UGX')
                            ->color('success')
                            ->size(TextEntry\TextEntrySize::Large),
                    ]),

                Section::make('Tenure')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('start_date')
                            ->date(),

                        TextEntry::make('maturity_date')
                            ->date()
                            ->color(fn ($state, FixedDeposit $record): string => $record->isDueForMaturity() ? 'warning' : 'gray'),

                        TextEntry::make('tenure_months')
                            ->label('Duration')
                            ->suffix(' months'),

                        TextEntry::make('rollover_count')
                            ->label('Rollovers'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFixedDeposits::route('/'),
            'create' => CreateFixedDeposit::route('/create'),
            'view' => ViewFixedDeposit::route('/{record}'),
            'edit' => EditFixedDeposit::route('/{record}/edit'),
        ];
    }
}
