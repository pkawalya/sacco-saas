<?php

namespace App\Filament\Tenant\Resources\CollectionsWorklists;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\CollectionsWorklists\Pages\ListCollectionsWorklists;
use App\Filament\Tenant\Resources\CollectionsWorklists\Pages\ViewCollectionsWorklist;
use App\Models\Tenant\CollectionsWorklist;
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

class CollectionsWorklistResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = CollectionsWorklist::class;

    protected static string $moduleKey = 'collections_engine';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Worklist';

    protected static ?string $recordTitleAttribute = 'loan_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_number')->label('Loan #')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('member_name')->searchable()->sortable(),
                TextColumn::make('dpd')->label('DPD')->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state <= 30 => 'warning',
                        $state <= 90 => 'danger',
                        default => 'gray',
                    })->badge(),
                TextColumn::make('delinquency_bucket')->label('Bucket')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'current' => 'success', '1-30' => 'info', '31-60' => 'warning',
                        '61-90' => 'danger', default => 'gray',
                    }),
                TextColumn::make('tier')->badge()
                    ->formatStateUsing(fn (int $state): string => CollectionsWorklist::TIERS[$state] ?? '')
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'info', 2 => 'warning', 3 => 'danger', 4 => 'gray', default => 'gray',
                    }),
                TextColumn::make('arrears_amount')->label('Arrears')->money('UGX')->sortable(),
                TextColumn::make('outstanding_balance')->label('Outstanding')->money('UGX')->sortable(),
                TextColumn::make('accrued_penalty')->label('Penalty')->money('UGX')->toggleable(),
                TextColumn::make('officer_name')->label('Officer')->placeholder('Unassigned')->toggleable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning', 'resolved' => 'success',
                        'written_off' => 'danger', 'legal' => 'gray', default => 'gray',
                    }),
            ])
            ->defaultSort('dpd', 'desc')
            ->filters([
                SelectFilter::make('delinquency_bucket')->label('Bucket')->options(CollectionsWorklist::BUCKETS),
                SelectFilter::make('tier')->options(CollectionsWorklist::TIERS),
                SelectFilter::make('status')->options(CollectionsWorklist::STATUSES),
            ])
            ->recordUrl(fn (CollectionsWorklist $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Loan Details')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('loan_number')->badge()->color('primary')->copyable(),
                        TextEntry::make('member_name'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'warning', 'resolved' => 'success',
                                'written_off' => 'danger', 'legal' => 'gray', default => 'gray',
                            }),
                        TextEntry::make('branch_code')->placeholder('—'),
                    ]),
                Section::make('Delinquency')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('dpd')->label('Days Past Due')->badge()
                            ->color(fn (int $state): string => $state > 90 ? 'danger' : ($state > 30 ? 'warning' : 'success')),
                        TextEntry::make('delinquency_bucket')->label('Bucket')->badge(),
                        TextEntry::make('tier')
                            ->formatStateUsing(fn (int $state): string => CollectionsWorklist::TIERS[$state] ?? '')
                            ->badge(),
                        TextEntry::make('officer_name')->placeholder('Unassigned'),
                        TextEntry::make('arrears_amount')->money('UGX'),
                        TextEntry::make('outstanding_balance')->money('UGX'),
                        TextEntry::make('instalment_amount')->money('UGX'),
                        TextEntry::make('accrued_penalty')->money('UGX'),
                        TextEntry::make('last_payment_date')->date()->placeholder('—'),
                        TextEntry::make('next_due_date')->date()->placeholder('—'),
                        TextEntry::make('escalated_at')->dateTime()->placeholder('—'),
                        TextEntry::make('resolved_at')->dateTime()->placeholder('—'),
                    ]),
                Section::make('Recent Activities')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('activities')
                            ->label('')
                            ->schema([
                                TextEntry::make('activity_type')->badge()->color('info'),
                                TextEntry::make('description'),
                                TextEntry::make('outcome')->badge()->placeholder('—'),
                                TextEntry::make('officer_name')->placeholder('—'),
                                TextEntry::make('created_at')->dateTime(),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Promise to Pay')
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('ptpRecords')
                            ->label('')
                            ->schema([
                                TextEntry::make('promised_amount')->money('UGX'),
                                TextEntry::make('promised_date')->date(),
                                TextEntry::make('status')->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'kept' => 'success', 'broken' => 'danger',
                                        'partial' => 'warning', default => 'gray',
                                    }),
                                TextEntry::make('actual_amount_paid')->money('UGX'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollectionsWorklists::route('/'),
            'view' => ViewCollectionsWorklist::route('/{record}'),
        ];
    }
}
