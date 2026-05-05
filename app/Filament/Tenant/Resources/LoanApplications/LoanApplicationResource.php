<?php

namespace App\Filament\Tenant\Resources\LoanApplications;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\LoanApplications\Pages\CreateLoanApplication;
use App\Filament\Tenant\Resources\LoanApplications\Pages\EditLoanApplication;
use App\Filament\Tenant\Resources\LoanApplications\Pages\ListLoanApplications;
use App\Filament\Tenant\Resources\LoanApplications\Pages\ViewLoanApplication;
use App\Models\Tenant\LoanApplication;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
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

class LoanApplicationResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = LoanApplication::class;

    protected static string $moduleKey = 'loan_management';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Loans';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'application_ref';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application Details')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columns(2)
                    ->schema([
                        TextInput::make('application_ref')->required()->unique(ignoreRecord: true),

                        Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'member_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->member_number}] {$record->full_name}")
                            ->searchable()->preload()->required(),

                        Select::make('product_id')
                            ->label('Product')->relationship('product', 'product_name')
                            ->searchable()->preload()->required(),

                        TextInput::make('amount_requested')->label('Amount Requested (UGX)')->numeric()->required(),
                        TextInput::make('tenure_months_requested')->label('Tenure (months)')->integer()->required(),

                        TextInput::make('purpose')->label('Loan Purpose')->maxLength(100),
                        Textarea::make('purpose_details')->label('Purpose Details')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Applicant Income / DSCR (FR-LM-010)')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(3)
                    ->schema([
                        TextInput::make('monthly_income')->label('Monthly Income (UGX)')->numeric(),
                        TextInput::make('monthly_expenses')->label('Monthly Expenses (UGX)')->numeric(),
                        TextInput::make('dscr')->label('DSCR Score')->numeric()->step(0.0001)->disabled()->dehydrated(false)->helperText('Auto-computed by system'),
                    ]),

                Section::make('Officer Recommendation')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns(2)
                    ->schema([
                        TextInput::make('amount_recommended')->label('Amount Recommended (UGX)')->numeric(),
                        TextInput::make('tenure_months_recommended')->label('Recommended Tenure (months)')->integer(),
                        Textarea::make('officer_notes')->label('Officer Notes')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Status')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->columns(2)
                    ->schema([
                        Select::make('status')->options(LoanApplication::STATUSES)->default(LoanApplication::STATUS_DRAFT)->required(),
                        TextInput::make('branch_code')->maxLength(20),
                        DateTimePicker::make('submitted_at')->label('Submitted At'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application_ref')->label('Ref #')->searchable()->sortable()->copyable(),
                TextColumn::make('member.full_name')->label('Member')->searchable()->sortable(),
                TextColumn::make('product.product_name')->label('Product')->searchable()->sortable(),
                TextColumn::make('amount_requested')->label('Requested')->money('UGX')->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'submitted', 'under_review' => 'warning',
                        'declined' => 'danger',
                        'withdrawn' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('dscr')->label('DSCR')->sortable()
                    ->color(fn ($state): string => ($state !== null && (float) $state >= 1.25) ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('submitted_at')->label('Submitted')->dateTime('d M Y')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(LoanApplication::STATUSES),
                SelectFilter::make('product_id')->label('Product')
                    ->relationship('product', 'product_name'),
            ])
            ->recordUrl(fn (LoanApplication $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Application')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('application_ref')->badge()->color('primary')->copyable(),
                        TextEntry::make('member.full_name')->label('Member'),
                        TextEntry::make('product.product_name')->label('Product'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'submitted', 'under_review' => 'warning',
                                'declined' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Request')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('amount_requested')->label('Requested')->money('UGX')->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('tenure_months_requested')->label('Tenure')->suffix(' months'),
                        TextEntry::make('purpose')->placeholder('—'),
                        TextEntry::make('purpose_details')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('DSCR Appraisal')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('monthly_income')->label('Monthly Income')->money('UGX'),
                        TextEntry::make('monthly_expenses')->label('Monthly Expenses')->money('UGX'),
                        TextEntry::make('dscr')->label('DSCR Score')
                            ->color(fn ($state): string => ((float) $state >= 1.25) ? 'success' : 'danger')
                            ->placeholder('Not computed'),
                        TextEntry::make('dscr_passed')->label('DSCR Passed')
                            ->formatStateUsing(fn (bool $state): string => $state ? '✓ Yes' : '✗ No')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanApplications::route('/'),
            'create' => CreateLoanApplication::route('/create'),
            'view' => ViewLoanApplication::route('/{record}'),
            'edit' => EditLoanApplication::route('/{record}/edit'),
        ];
    }
}
