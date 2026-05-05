<?php

namespace App\Filament\Tenant\Resources\Budgets;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Tenant\Resources\Budgets\Pages\EditBudget;
use App\Filament\Tenant\Resources\Budgets\Pages\ListBudgets;
use App\Filament\Tenant\Resources\Budgets\Pages\ViewBudget;
use App\Models\Tenant\Budget;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = Budget::class;

    protected static string $moduleKey = 'revenue_expense';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Revenue & Expense';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'budget_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Budget Details')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->columns(2)
                    ->schema([
                        TextInput::make('budget_code')->required()->unique(ignoreRecord: true)->maxLength(30),
                        TextInput::make('budget_name')->required()->maxLength(150),
                        Select::make('gl_account_id')
                            ->label('GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->pluck('account_name', 'id')->toArray())
                            ->searchable()->required(),
                        TextInput::make('cost_centre_code')->maxLength(30)->nullable(),
                        TextInput::make('fiscal_year')->numeric()->required()->default(now()->year),
                        Select::make('period')->options(Budget::PERIODS)->default('annual')->required(),
                        DatePicker::make('start_date')->required(),
                        DatePicker::make('end_date')->required(),
                        Textarea::make('description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Amounts (3-Tier)')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextInput::make('original_amount')->numeric()->default(0)->prefix('UGX'),
                        TextInput::make('revised_amount')->numeric()->default(0)->prefix('UGX'),
                        TextInput::make('approved_amount')->numeric()->default(0)->prefix('UGX'),
                    ]),

                Section::make('Controls')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(3)
                    ->schema([
                        Select::make('status')->options(Budget::STATUSES)->default('draft')->required(),
                        TextInput::make('variance_threshold_pct')->label('Variance Threshold (%)')->numeric()->default(10)->suffix('%'),
                        Toggle::make('enforce_budget')->label('Enforce (block over-budget)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('budget_code')->label('Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('budget_name')->label('Budget')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active', 'approved' => 'success', 'submitted' => 'warning',
                        'draft' => 'gray', 'closed' => 'info', default => 'gray',
                    }),
                TextColumn::make('glAccount.account_code')->label('GL'),
                TextColumn::make('fiscal_year')->sortable(),
                TextColumn::make('approved_amount')->money('UGX')->sortable(),
                TextColumn::make('actual_amount')->money('UGX'),
                TextColumn::make('remaining')->label('Remaining')->money('UGX')
                    ->state(fn (Budget $record): float => $record->remaining)
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('variance_percentage')->label('Used %')->suffix('%')
                    ->state(fn (Budget $record): float => $record->variance_percentage)
                    ->color(fn (float $state): string => $state > 100 ? 'danger' : ($state > 80 ? 'warning' : 'success')),
                IconColumn::make('enforce_budget')->label('Enforced')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(Budget::STATUSES),
                SelectFilter::make('period')->options(Budget::PERIODS),
                SelectFilter::make('fiscal_year')
                    ->options(fn (): array => Budget::query()->distinct()->pluck('fiscal_year', 'fiscal_year')->toArray()),
            ])
            ->recordUrl(fn (Budget $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Budget')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('budget_code')->badge()->color('primary')->copyable(),
                        TextEntry::make('budget_name'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active', 'approved' => 'success', 'submitted' => 'warning',
                                'draft' => 'gray', 'closed' => 'info', default => 'gray',
                            }),
                        TextEntry::make('glAccount.account_name')->label('GL Account'),
                        TextEntry::make('cost_centre_code')->placeholder('—'),
                        TextEntry::make('fiscal_year'),
                        TextEntry::make('period')->badge()->color('gray'),
                        TextEntry::make('start_date')->date(),
                        TextEntry::make('end_date')->date(),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Amounts')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('original_amount')->money('UGX'),
                        TextEntry::make('revised_amount')->money('UGX'),
                        TextEntry::make('approved_amount')->money('UGX'),
                        TextEntry::make('actual_amount')->money('UGX'),
                    ]),
                Section::make('Variance Analysis')
                    ->icon(Heroicon::OutlinedChartPie)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('remaining')->label('Remaining')->money('UGX')
                            ->state(fn (Budget $record): float => $record->remaining),
                        TextEntry::make('variance')->money('UGX')
                            ->state(fn (Budget $record): float => $record->variance),
                        TextEntry::make('utilisation')->label('Utilisation %')->suffix('%')
                            ->state(fn (Budget $record): float => $record->utilisation),
                        IconEntry::make('over_threshold')->label('Over Threshold')
                            ->state(fn (Budget $record): bool => $record->isOverThreshold())
                            ->boolean(),
                    ]),
                Section::make('Controls')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('variance_threshold_pct')->suffix('%'),
                        IconEntry::make('enforce_budget')->boolean(),
                        TextEntry::make('approved_by')->placeholder('—'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'view' => ViewBudget::route('/{record}'),
            'edit' => EditBudget::route('/{record}/edit'),
        ];
    }
}
