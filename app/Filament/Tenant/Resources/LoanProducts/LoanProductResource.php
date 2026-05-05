<?php

namespace App\Filament\Tenant\Resources\LoanProducts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\LoanProducts\Pages\CreateLoanProduct;
use App\Filament\Tenant\Resources\LoanProducts\Pages\EditLoanProduct;
use App\Filament\Tenant\Resources\LoanProducts\Pages\ListLoanProducts;
use App\Filament\Tenant\Resources\LoanProducts\Pages\ViewLoanProduct;
use App\Models\Tenant\LoanProduct;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class LoanProductResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = LoanProduct::class;

    protected static string $moduleKey = 'loan_management';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Loans';

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
                        TextInput::make('product_code')->required()->unique(ignoreRecord: true)->maxLength(20),
                        TextInput::make('product_name')->required()->maxLength(100),
                        Select::make('product_type')->options(LoanProduct::TYPES)->required()->default(LoanProduct::TYPE_TERM),
                        Textarea::make('description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Interest & Method')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(3)
                    ->schema([
                        TextInput::make('interest_rate')->label('Annual Rate (%)')->numeric()->step(0.0001)->suffix('%')->required(),
                        Select::make('interest_method')->options(LoanProduct::METHODS)->default(LoanProduct::METHOD_REDUCING)->required(),
                        Select::make('interest_period')->options(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'])->default('monthly')->required(),
                    ]),

                Section::make('Fees & Penalties')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(3)
                    ->schema([
                        TextInput::make('processing_fee_rate')->label('Processing Fee (%)')->numeric()->step(0.01)->suffix('%')->default(0),
                        TextInput::make('processing_fee_fixed')->label('Processing Fee (Fixed UGX)')->numeric()->default(0),
                        Toggle::make('processing_fee_upfront')->label('Fee Collected Upfront')->default(true),
                        TextInput::make('maintenance_fee_monthly')->label('Monthly Maintenance Fee (UGX)')->numeric()->default(0),
                        TextInput::make('insurance_rate')->label('Insurance Rate (%)')->numeric()->step(0.0001)->suffix('%')->default(0),
                        TextInput::make('penalty_rate_daily')->label('Daily Penalty (%)')->numeric()->step(0.0001)->suffix('%')->default(0),
                        TextInput::make('grace_period_days')->label('Grace Period (days)')->integer()->default(0),
                    ]),

                Section::make('Tenure & Amounts')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->columns(3)
                    ->schema([
                        TextInput::make('minimum_tenure_months')->label('Min Tenure (months)')->integer()->required()->default(1),
                        TextInput::make('maximum_tenure_months')->label('Max Tenure (months)')->integer()->required()->default(60),
                        TextInput::make('minimum_amount')->label('Min Amount (UGX)')->numeric()->default(0),
                        TextInput::make('maximum_amount')->label('Max Amount (UGX)')->numeric()->nullable(),
                        TextInput::make('maximum_multiplier')->label('Max Multiplier (× savings)')->numeric()->step(0.01)->nullable()->helperText('E.g. 3 = up to 3× member savings'),
                    ]),

                Section::make('Guarantors & Collateral')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(3)
                    ->schema([
                        TextInput::make('minimum_guarantors')->label('Min Guarantors')->integer()->default(0),
                        TextInput::make('maximum_guarantors')->label('Max Guarantors')->integer()->default(0),
                        Toggle::make('collateral_required')->label('Collateral Required'),
                        TextInput::make('minimum_coverage_ratio')->label('Min Coverage Ratio')->numeric()->step(0.01)->default(1.00)->helperText('e.g. 1.25 = 125% coverage'),
                    ]),

                Section::make('Disbursement & Controls')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(2)
                    ->schema([
                        Toggle::make('four_eyes_disbursement')->label('Require Four-Eyes Disbursement')->default(true),
                        Toggle::make('is_active')->label('Product Active')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_code')->label('Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('product_name')->label('Product')->searchable()->sortable(),
                TextColumn::make('product_type')
                    ->label('Type')->badge()
                    ->formatStateUsing(fn (string $state): string => LoanProduct::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'emergency' => 'danger',
                        'mortgage' => 'info',
                        'group' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('interest_rate')->label('Rate')->suffix('% p.a.')->sortable(),
                TextColumn::make('interest_method')
                    ->formatStateUsing(fn (string $state): string => LoanProduct::METHODS[$state] ?? $state)
                    ->toggleable(),
                TextColumn::make('minimum_tenure_months')->label('Min Months')->sortable()->toggleable(),
                TextColumn::make('maximum_tenure_months')->label('Max Months')->sortable()->toggleable(),
                TextColumn::make('loans_count')->label('Loans')->counts('loans')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                IconColumn::make('four_eyes_disbursement')->label('4-Eyes')->boolean()->toggleable(),
            ])
            ->defaultSort('product_code')
            ->filters([
                SelectFilter::make('product_type')->options(LoanProduct::TYPES),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordUrl(fn (LoanProduct $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Product Details')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product_code')->badge()->color('primary'),
                        TextEntry::make('product_name'),
                        TextEntry::make('product_type')->badge()
                            ->formatStateUsing(fn (string $state): string => LoanProduct::TYPES[$state] ?? $state),
                        TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('Interest')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('interest_rate')->suffix('% p.a.'),
                        TextEntry::make('interest_method')->formatStateUsing(fn (string $state): string => LoanProduct::METHODS[$state] ?? $state),
                        TextEntry::make('interest_period'),
                        TextEntry::make('grace_period_days')->label('Grace Period')->suffix(' days'),
                    ]),

                Section::make('Fees')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('processing_fee_rate')->suffix('%'),
                        TextEntry::make('processing_fee_fixed')->money('UGX'),
                        TextEntry::make('insurance_rate')->label('Insurance Rate')->suffix('%'),
                        TextEntry::make('penalty_rate_daily')->label('Daily Penalty')->suffix('%'),
                        TextEntry::make('maintenance_fee_monthly')->label('Monthly Fee')->money('UGX'),
                    ]),

                Section::make('Limits')
                    ->icon(Heroicon::OutlinedArrowsPointingIn)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('minimum_amount')->money('UGX'),
                        TextEntry::make('maximum_amount')->money('UGX')->placeholder('No limit'),
                        TextEntry::make('maximum_multiplier')->suffix('× savings')->placeholder('—'),
                        TextEntry::make('minimum_tenure_months')->suffix(' months'),
                        TextEntry::make('maximum_tenure_months')->suffix(' months'),
                        TextEntry::make('minimum_coverage_ratio')->label('Coverage Ratio'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanProducts::route('/'),
            'create' => CreateLoanProduct::route('/create'),
            'view' => ViewLoanProduct::route('/{record}'),
            'edit' => EditLoanProduct::route('/{record}/edit'),
        ];
    }
}
