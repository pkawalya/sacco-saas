<?php

namespace App\Filament\Tenant\Resources\JournalEntries;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Tenant\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Tenant\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = JournalEntry::class;

    protected static string $moduleKey = 'general_ledger';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'journal_number';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journal_number')
                    ->label('Journal #')->searchable()->sortable()->copyable(),
                TextColumn::make('transaction_date')->date()->sortable(),
                TextColumn::make('description')->searchable()->sortable()->limit(50),
                TextColumn::make('journal_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => JournalEntry::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'system' => 'info',
                        'manual' => 'warning',
                        'auto_reversal' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('total_debit')->label('Debit')->money('UGX')->sortable(),
                TextColumn::make('total_credit')->label('Credit')->money('UGX')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        'draft' => 'warning',
                        'reversed' => 'danger',
                        'void' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('period.period_name')->label('Period')->toggleable(),
                TextColumn::make('source_module')->label('Source')->toggleable(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(JournalEntry::STATUSES),
                SelectFilter::make('journal_type')->label('Type')->options(JournalEntry::TYPES),
            ])
            ->recordUrl(fn (JournalEntry $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Journal Entry')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('journal_number')->badge()->color('primary')->copyable(),
                        TextEntry::make('journal_type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => JournalEntry::TYPES[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'system' => 'info',
                                'manual' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('transaction_date')->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'posted' => 'success',
                                'draft' => 'warning',
                                'reversed' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Details')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('total_debit')->money('UGX')->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('total_credit')->money('UGX')->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('source_module')->placeholder('—'),
                        TextEntry::make('source_reference')->placeholder('—'),
                        TextEntry::make('period.period_name')->label('Period')->placeholder('—'),
                        TextEntry::make('posted_at')->dateTime()->placeholder('Not posted'),
                    ]),

                Section::make('Journal Lines')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('account.account_code')->label('Code'),
                                TextEntry::make('account.account_name')->label('Account'),
                                TextEntry::make('debit')->money('UGX'),
                                TextEntry::make('credit')->money('UGX'),
                                TextEntry::make('narration')->placeholder('—'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view' => ViewJournalEntry::route('/{record}'),
        ];
    }
}
