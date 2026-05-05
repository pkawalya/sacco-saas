<?php

namespace App\Filament\Tenant\Resources\Loans;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\Loans\Pages\CreateLoan;
use App\Filament\Tenant\Resources\Loans\Pages\EditLoan;
use App\Filament\Tenant\Resources\Loans\Pages\ListLoans;
use App\Filament\Tenant\Resources\Loans\Pages\ViewLoan;
use App\Filament\Tenant\Resources\Loans\RelationManagers\ApprovalsRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\CollateralRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\GuarantorsRelationManager;
use App\Filament\Tenant\Resources\Loans\RelationManagers\RepaymentsRelationManager;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanProduct;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoanResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = Loan::class;

    protected static string $moduleKey = 'loan_management';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_TELLER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Loans';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'loan_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Loan Details')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(2)
                    ->schema([
                        TextInput::make('loan_number')->required()->unique(ignoreRecord: true),

                        Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'member_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->member_number}] {$record->full_name}")
                            ->searchable()->preload()->required(),

                        Select::make('product_id')
                            ->label('Loan Product')
                            ->relationship('product', 'product_name')
                            ->searchable()->preload()->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Clear collateral fields when product changes
                                $set('collateral_name', null);
                                $set('collateral_type', null);
                                $set('collateral_type_other', null);
                                $set('collateral_location', null);
                                $set('collateral_value', null);
                                $set('collateral_documents', []);
                            }),

                        Select::make('status')
                            ->options(Loan::STATUSES)
                            ->default(Loan::STATUS_APPROVED)
                            ->required(),

                        TextInput::make('principal_amount')->label('Principal')->numeric()->prefix('UGX')->required(),
                        TextInput::make('approved_amount')->label('Approved Amount')->numeric()->prefix('UGX')->required(),
                        TextInput::make('tenure_months')->label('Period (months)')->integer()->required(),
                        TextInput::make('interest_rate')->label('Rate (%)')->numeric()->step(0.0001)->suffix('%')->required(),

                        Select::make('interest_method')
                            ->options(LoanProduct::METHODS)
                            ->default(LoanProduct::METHOD_REDUCING)
                            ->required(),

                        DatePicker::make('first_repayment_date')->label('First Repayment Date'),
                        DatePicker::make('expected_maturity_date')->label('Due date'),

                        TextInput::make('branch_code')->maxLength(20),

                        Select::make('disbursement_channel')
                            ->options(['cash' => 'Cash', 'mobile' => 'Mobile Money', 'eft' => 'EFT / Bank Transfer']),
                    ]),

                Section::make('Collateral Information')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns(2)
                    ->visible(function (callable $get) {
                        $productId = $get('product_id');
                        if (! $productId) {
                            return false;
                        }
                        $product = LoanProduct::find($productId);

                        return $product && $product->collateral_required;
                    })
                    ->schema([
                        TextInput::make('collateral_name')
                            ->label('Collateral Name')
                            ->required(),

                        Select::make('collateral_type')
                            ->label('Collateral Type')
                            ->options([
                                'property' => 'Property',
                                'vehicle' => 'Vehicle',
                                'equipment' => 'Equipment',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('collateral_type_other')
                            ->label('Specify Other Type')
                            ->visible(fn (callable $get) => $get('collateral_type') === 'other')
                            ->required(fn (callable $get) => $get('collateral_type') === 'other'),

                        TextInput::make('collateral_location')
                            ->label('Collateral Location')
                            ->placeholder('Address or location details')
                            ->required(),

                        TextInput::make('collateral_value')
                            ->label('Estimated Value')
                            ->numeric()
                            ->prefix('UGX')
                            ->required(),

                        FileUpload::make('collateral_documents')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->directory('loans/collateral')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->uploadProgressIndicatorPosition('left'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_number')
                    ->label('Loan #')->searchable()->sortable()->copyable(),

                TextColumn::make('member.full_name')
                    ->label('Member')->searchable()->sortable(),

                TextColumn::make('product.product_name')
                    ->label('Product')->searchable()->sortable(),

                TextColumn::make('approved_amount')
                    ->label('Principal')->money('UGX')->sortable(),

                TextColumn::make('total_outstanding')
                    ->label('Outstanding')->money('UGX')->sortable()
                    ->color(fn (Loan $record): string => (float) $record->total_outstanding > 0 ? 'warning' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'approved' => 'info',
                        'completed' => 'gray',
                        'written_off' => 'gray',
                        'restructured' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('par_bucket')
                    ->label('PAR')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'current' => 'success',
                        '1-30' => 'warning',
                        '31-60' => 'warning',
                        '61-90' => 'danger',
                        '91-180' => 'danger',
                        '180+' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('days_past_due')
                    ->label('DPD')
                    ->suffix('d')
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('expected_maturity_date')
                    ->label('Due date')->date()->sortable()->toggleable(),

                TextColumn::make('branch_code')->label('Branch')->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(Loan::STATUSES),

                SelectFilter::make('par_bucket')
                    ->label('PAR Bucket')
                    ->options([
                        'current' => 'Current',
                        '1-30' => '1–30 Days',
                        '31-60' => '31–60 Days',
                        '61-90' => '61–90 Days',
                        '91-180' => '91–180 Days',
                        '180+' => '180+ Days',
                    ]),

                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => LoanProduct::query()->pluck('product_name', 'id')),

                Filter::make('in_arrears')
                    ->label('In Arrears Only')
                    ->query(fn ($query) => $query->where('days_past_due', '>', 0)),
            ])
            ->recordUrl(fn (Loan $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Loan Summary')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('loan_number')->badge()->color('primary')->copyable(),
                        TextEntry::make('member.full_name')->label('Member'),
                        TextEntry::make('product.product_name')->label('Product'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'approved' => 'info',
                                'completed' => 'gray',
                                'written_off' => 'gray',
                                'restructured' => 'warning',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Financials')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('approved_amount')->label('Approved')->money('UGX')->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('outstanding_principal')->label('Outstanding Principal')->money('UGX'),
                        TextEntry::make('outstanding_interest')->label('Outstanding Interest')->money('UGX'),
                        TextEntry::make('outstanding_penalty')->label('Outstanding Penalty')->money('UGX')->color('danger'),
                        TextEntry::make('total_outstanding')->label('Total Outstanding')->money('UGX')
                            ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'success')
                            ->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('monthly_instalment')->label('Monthly Instalment')->money('UGX'),
                        TextEntry::make('interest_rate')->label('Rate')->suffix('% p.a.'),
                        TextEntry::make('tenure_months')->label('Period')->suffix(' months'),
                    ]),

                Section::make('PAR & Arrears')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('days_past_due')->label('Days Past Due')->suffix(' days')
                            ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'success'),
                        TextEntry::make('par_bucket')->label('PAR Bucket')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'current' => 'success',
                                '1-30', '31-60' => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('amount_in_arrears')->label('Amount in Arrears')->money('UGX')->color('danger'),
                        TextEntry::make('last_repayment_date')->label('Last Repayment')->date()->placeholder('No repayments'),
                    ]),

                Section::make('Repayment Schedule')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('schedule')
                            ->label('')
                            ->schema([
                                TextEntry::make('instalment_number')->label('#'),
                                TextEntry::make('due_date')->date(),
                                TextEntry::make('total_due')->label('Due')->money('UGX'),
                                TextEntry::make('total_paid')->label('Paid')->money('UGX'),
                                TextEntry::make('status')->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'overdue' => 'danger',
                                        'waived' => 'gray',
                                        default => 'info',
                                    }),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ApprovalsRelationManager::class,
            GuarantorsRelationManager::class,
            CollateralRelationManager::class,
            RepaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'view' => ViewLoan::route('/{record}'),
            'edit' => EditLoan::route('/{record}/edit'),
        ];
    }
}
