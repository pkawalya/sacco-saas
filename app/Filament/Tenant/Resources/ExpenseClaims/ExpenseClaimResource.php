<?php

namespace App\Filament\Tenant\Resources\ExpenseClaims;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\ExpenseClaims\Pages\CreateExpenseClaim;
use App\Filament\Tenant\Resources\ExpenseClaims\Pages\EditExpenseClaim;
use App\Filament\Tenant\Resources\ExpenseClaims\Pages\ListExpenseClaims;
use App\Filament\Tenant\Resources\ExpenseClaims\Pages\ViewExpenseClaim;
use App\Models\Tenant\Budget;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\ExpenseClaim;
use App\Models\Tenant\User;
use App\Services\Tenant\RevenueExpenseService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseClaimResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = ExpenseClaim::class;

    protected static string $moduleKey = 'revenue_expense';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Revenue & Expense';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'claim_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Details')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->columns(2)
                    ->schema([
                        TextInput::make('claim_number')->required()->unique(ignoreRecord: true)->maxLength(30)
                            ->default(fn (): string => app(RevenueExpenseService::class)->generateClaimNumber()),
                        TextInput::make('claimant_name')->required()->maxLength(150),
                        Select::make('category')->options(ExpenseClaim::CATEGORIES)->required(),
                        DatePicker::make('expense_date')->required(),
                        Select::make('gl_account_id')
                            ->label('GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->ofType('expense')->pluck('account_name', 'id')->toArray())
                            ->searchable()->required(),
                        TextInput::make('cost_centre_code')->maxLength(30)->nullable(),
                        TextInput::make('claimed_amount')->numeric()->required()->prefix('UGX'),
                        Select::make('budget_id')
                            ->label('Budget Line')
                            ->options(fn (): array => Budget::query()->active()->pluck('budget_name', 'id')->toArray())
                            ->searchable()->nullable(),
                        Textarea::make('description')->required()->rows(3)->columnSpanFull(),
                        FileUpload::make('receipt_path')->label('Receipt/Invoice')->directory('expense-receipts')->columnSpanFull(),
                    ]),

                Section::make('Approval')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->columns(2)
                    ->schema([
                        Select::make('status')->options(ExpenseClaim::STATUSES)->default('draft')->required(),
                        TextInput::make('approved_amount')->numeric()->prefix('UGX')->nullable(),
                        Textarea::make('rejection_reason')->rows(2)->nullable()->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('claim_number')->label('Claim #')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('claimant_name')->label('Claimant')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'paid' => 'success', 'submitted', 'under_review' => 'warning',
                        'rejected' => 'danger', 'draft' => 'gray', default => 'gray',
                    }),
                TextColumn::make('category')->badge()->color('gray'),
                TextColumn::make('claimed_amount')->money('UGX')->sortable(),
                TextColumn::make('approved_amount')->money('UGX')->placeholder('—'),
                TextColumn::make('expense_date')->date()->sortable(),
                TextColumn::make('budget.budget_code')->label('Budget')->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(ExpenseClaim::STATUSES),
                SelectFilter::make('category')->options(ExpenseClaim::CATEGORIES),
                Filter::make('expense_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, string $date): Builder => $q->whereDate('expense_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, string $date): Builder => $q->whereDate('expense_date', '<=', $date));
                    }),
            ])
            ->recordUrl(fn (ExpenseClaim $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Claim Details')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('claim_number')->badge()->color('primary')->copyable(),
                        TextEntry::make('claimant_name'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved', 'paid' => 'success', 'submitted', 'under_review' => 'warning',
                                'rejected' => 'danger', 'draft' => 'gray', default => 'gray',
                            }),
                        TextEntry::make('category')->badge()->color('gray'),
                        TextEntry::make('expense_date')->date(),
                        TextEntry::make('glAccount.account_name')->label('GL Account'),
                        TextEntry::make('cost_centre_code')->placeholder('—'),
                        TextEntry::make('budget.budget_code')->label('Budget')->placeholder('—'),
                        TextEntry::make('description')->columnSpanFull(),
                    ]),
                Section::make('Financials')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('claimed_amount')->money('UGX'),
                        TextEntry::make('approved_amount')->money('UGX')->placeholder('—'),
                        TextEntry::make('currency'),
                    ]),
                Section::make('Approval Trail')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('reviewed_by')->placeholder('—'),
                        TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approved_by')->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                        TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseClaims::route('/'),
            'create' => CreateExpenseClaim::route('/create'),
            'view' => ViewExpenseClaim::route('/{record}'),
            'edit' => EditExpenseClaim::route('/{record}/edit'),
        ];
    }
}
