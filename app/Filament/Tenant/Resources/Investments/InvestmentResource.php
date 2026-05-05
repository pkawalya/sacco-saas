<?php

namespace App\Filament\Tenant\Resources\Investments;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\Investments\Pages\CreateInvestment;
use App\Filament\Tenant\Resources\Investments\Pages\EditInvestment;
use App\Filament\Tenant\Resources\Investments\Pages\ListInvestments;
use App\Filament\Tenant\Resources\Investments\Pages\ViewInvestment;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\Investment;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvestmentResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = Investment::class;

    protected static string $moduleKey = 'revenue_expense';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Revenue & Expense';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Investment Details')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columns(2)
                    ->schema([
                        TextInput::make('investment_code')->required()->unique(ignoreRecord: true)->maxLength(30),
                        TextInput::make('name')->required()->maxLength(200),
                        Select::make('investment_type')->options(Investment::TYPES)->required(),
                        TextInput::make('counterparty')->maxLength(200)->nullable(),
                        Select::make('gl_account_id')
                            ->label('Asset GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->ofType('asset')->pluck('account_name', 'id')->toArray())
                            ->searchable()->required(),
                        Select::make('income_account_id')
                            ->label('Income GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->ofType('revenue')->pluck('account_name', 'id')->toArray())
                            ->searchable()->nullable(),
                        TextInput::make('reference_number')->maxLength(50)->nullable(),
                        Select::make('status')->options(Investment::STATUSES)->default('active')->required(),
                        Textarea::make('description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Financials')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextInput::make('face_value')->numeric()->required()->prefix('UGX'),
                        TextInput::make('purchase_price')->numeric()->required()->prefix('UGX'),
                        TextInput::make('current_value')->numeric()->required()->prefix('UGX'),
                        TextInput::make('accrued_income')->numeric()->default(0)->prefix('UGX'),
                        TextInput::make('interest_rate')->numeric()->default(0)->suffix('%'),
                        TextInput::make('expected_return')->numeric()->default(0)->prefix('UGX'),
                    ]),

                Section::make('Dates')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columns(3)
                    ->schema([
                        DatePicker::make('purchase_date')->required(),
                        DatePicker::make('maturity_date')->nullable(),
                        DatePicker::make('last_valuation_date')->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('investment_code')->label('Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'matured' => 'info',
                        'sold' => 'warning', 'written_off' => 'danger', default => 'gray',
                    }),
                TextColumn::make('investment_type')->label('Type')->badge()->color('gray'),
                TextColumn::make('counterparty')->placeholder('—')->toggleable(),
                TextColumn::make('purchase_price')->money('UGX')->sortable(),
                TextColumn::make('current_value')->money('UGX')->sortable(),
                TextColumn::make('roi')->label('ROI %')->suffix('%')
                    ->state(fn (Investment $record): float => $record->roi)
                    ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('interest_rate')->label('Rate %')->suffix('%')->alignCenter(),
                TextColumn::make('maturity_date')->date()->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('investment_type')->options(Investment::TYPES),
                SelectFilter::make('status')->options(Investment::STATUSES),
            ])
            ->recordUrl(fn (Investment $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Investment')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('investment_code')->badge()->color('primary')->copyable(),
                        TextEntry::make('name'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success', 'matured' => 'info',
                                'sold' => 'warning', 'written_off' => 'danger', default => 'gray',
                            }),
                        TextEntry::make('investment_type')->badge()->color('gray'),
                        TextEntry::make('counterparty')->placeholder('—'),
                        TextEntry::make('reference_number')->placeholder('—'),
                        TextEntry::make('glAccount.account_name')->label('Asset GL Account'),
                        TextEntry::make('incomeAccount.account_name')->label('Income GL Account')->placeholder('—'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Financials')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('face_value')->money('UGX'),
                        TextEntry::make('purchase_price')->money('UGX'),
                        TextEntry::make('current_value')->money('UGX'),
                        TextEntry::make('accrued_income')->money('UGX'),
                        TextEntry::make('interest_rate')->suffix('%'),
                        TextEntry::make('expected_return')->money('UGX'),
                    ]),
                Section::make('Performance')
                    ->icon(Heroicon::OutlinedArrowTrendingUp)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('unrealised_gain_loss')->label('Unrealised Gain/Loss')->money('UGX')
                            ->state(fn (Investment $record): float => $record->unrealised_gain_loss)
                            ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                        TextEntry::make('roi')->label('ROI')->suffix('%')
                            ->state(fn (Investment $record): float => $record->roi)
                            ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                        TextEntry::make('days_to_maturity')->label('Days to Maturity')
                            ->state(fn (Investment $record): int => $record->days_to_maturity)
                            ->suffix(' days'),
                    ]),
                Section::make('Dates')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('purchase_date')->date(),
                        TextEntry::make('maturity_date')->date()->placeholder('—'),
                        TextEntry::make('last_valuation_date')->date()->placeholder('—'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvestments::route('/'),
            'create' => CreateInvestment::route('/create'),
            'view' => ViewInvestment::route('/{record}'),
            'edit' => EditInvestment::route('/{record}/edit'),
        ];
    }
}
