<?php

namespace App\Filament\Tenant\Resources\NotificationLogs;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Filament\Tenant\Resources\NotificationLogs\Pages\ViewNotificationLog;
use App\Models\Tenant\NotificationLog;
use App\Models\Tenant\NotificationTemplate;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
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

class NotificationLogResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = NotificationLog::class;

    protected static string $moduleKey = 'notifications_engine';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Delivery Log';

    protected static ?string $modelLabel = 'Notification Log';

    protected static ?string $pluralModelLabel = 'Notification Logs';

    /**
     * No create/edit — this is an immutable audit log (FR-AN-041).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ─── FROZEN IDENTITY COLUMNS ─────────
                TextColumn::make('id')
                    ->label('Log #')
                    ->sortable(),

                TextColumn::make('recipient_identifier')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sms' => 'success',
                        'email' => 'info',
                        'push' => 'warning',
                        'in_app' => 'gray',
                        default => 'gray',
                    }),

                // ─── SCROLLABLE DATA COLUMNS ─────────
                TextColumn::make('event_type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'sent' => 'info',
                        'pending', 'queued' => 'warning',
                        'failed', 'bounced' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('recipient_type')
                    ->toggleable(),

                TextColumn::make('failover_channel')
                    ->label('Failover')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->alignCenter(),

                TextColumn::make('provider')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sent_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('channel')
                    ->options(NotificationTemplate::CHANNELS),
                SelectFilter::make('status')
                    ->options(NotificationLog::STATUSES),
                SelectFilter::make('priority')
                    ->options(NotificationTemplate::PRIORITIES),
                SelectFilter::make('recipient_type')
                    ->options(NotificationLog::RECIPIENT_TYPES),
                SelectFilter::make('event_type')
                    ->options(fn (): array => NotificationLog::query()
                        ->distinct()
                        ->pluck('event_type', 'event_type')
                        ->toArray()),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordUrl(fn (NotificationLog $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Delivery Details')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Log #')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('template.template_code')
                            ->label('Template')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Direct (no template)'),
                        TextEntry::make('event_type')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('channel')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'sms' => 'success',
                                'email' => 'info',
                                'push' => 'warning',
                                'in_app' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'delivered' => 'success',
                                'sent' => 'info',
                                'pending', 'queued' => 'warning',
                                'failed', 'bounced' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'normal' => 'info',
                                'low' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Recipient')
                    ->icon(Heroicon::OutlinedUser)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('recipient_type')
                            ->badge(),
                        TextEntry::make('recipient_id')
                            ->label('Recipient ID')
                            ->placeholder('—'),
                        TextEntry::make('recipient_identifier')
                            ->label('Address')
                            ->copyable(),
                    ]),

                Section::make('Content')
                    ->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->schema([
                        TextEntry::make('subject')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                        TextEntry::make('rendered_body')
                            ->label('Body')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Delivery Tracking')
                    ->icon(Heroicon::OutlinedSignal)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('attempt_count')
                            ->label('Attempts'),
                        TextEntry::make('max_attempts')
                            ->label('Max Attempts'),
                        TextEntry::make('failover_channel')
                            ->label('Failover Channel')
                            ->placeholder('None'),
                        TextEntry::make('provider')
                            ->placeholder('—'),
                        TextEntry::make('external_id')
                            ->label('Provider ID')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('error_message')
                            ->label('Error')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Source')
                    ->icon(Heroicon::OutlinedLink)
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('source_module')
                            ->placeholder('—'),
                        TextEntry::make('source_reference')
                            ->placeholder('—'),
                        TextEntry::make('source_id')
                            ->placeholder('—'),
                    ]),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('queued_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('sent_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('delivered_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('failed_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
            'view' => ViewNotificationLog::route('/{record}'),
        ];
    }
}
