<?php

namespace App\Filament\Tenant\Resources\NotificationTemplates;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\NotificationTemplates\Pages\CreateNotificationTemplate;
use App\Filament\Tenant\Resources\NotificationTemplates\Pages\EditNotificationTemplate;
use App\Filament\Tenant\Resources\NotificationTemplates\Pages\ListNotificationTemplates;
use App\Filament\Tenant\Resources\NotificationTemplates\Pages\ViewNotificationTemplate;
use App\Models\Tenant\NotificationTemplate;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
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

class NotificationTemplateResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = NotificationTemplate::class;

    protected static string $moduleKey = 'notifications_engine';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columns(2)
                    ->schema([
                        TextInput::make('template_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g. LOAN_APPROVED')
                            ->helperText('Unique code identifier for this template'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('event_type')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g. loan.approved')
                            ->helperText('Dot-notation event key'),

                        Select::make('module')
                            ->options(collect(config('modules'))->mapWithKeys(
                                fn (array $m, string $key): array => [$key => $m['label']]
                            ))
                            ->searchable()
                            ->nullable(),

                        Select::make('channel')
                            ->options(NotificationTemplate::CHANNELS)
                            ->default(NotificationTemplate::CHANNEL_SMS)
                            ->required(),

                        Select::make('priority')
                            ->options(NotificationTemplate::PRIORITIES)
                            ->default(NotificationTemplate::PRIORITY_NORMAL)
                            ->required(),
                    ]),

                Section::make('Content')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->schema([
                        TextInput::make('subject')
                            ->maxLength(255)
                            ->placeholder('Email subject — supports merge fields like {member_name}')
                            ->helperText('Only used for email channel'),

                        Textarea::make('body')
                            ->required()
                            ->rows(5)
                            ->placeholder('Template body with merge fields: {member_name}, {amount}, {reference}')
                            ->helperText('Use curly braces for merge fields'),

                        KeyValue::make('merge_fields')
                            ->label('Available Merge Fields')
                            ->keyLabel('Field Key')
                            ->valueLabel('Sample Value')
                            ->helperText('Define merge field keys and sample values for documentation'),
                    ]),

                Section::make('Controls')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Toggle::make('is_mandatory')
                            ->label('Mandatory (cannot be opted out)')
                            ->helperText('Member cannot disable this notification'),
                        Toggle::make('mask_sensitive_data')
                            ->label('Mask Sensitive Data')
                            ->default(true)
                            ->helperText('Mask account numbers, phone numbers in content'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ─── FROZEN IDENTITY COLUMNS ─────────
                TextColumn::make('template_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()

                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
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

                TextColumn::make('module')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),

                IconColumn::make('is_mandatory')
                    ->label('Mandatory')
                    ->boolean(),

                IconColumn::make('mask_sensitive_data')
                    ->label('Mask')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('template_code')
            ->filters([
                SelectFilter::make('channel')
                    ->options(NotificationTemplate::CHANNELS),
                SelectFilter::make('priority')
                    ->options(NotificationTemplate::PRIORITIES),
                SelectFilter::make('module')
                    ->options(collect(config('modules'))->mapWithKeys(
                        fn (array $m, string $key): array => [$key => $m['label']]
                    )),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('is_mandatory')
                    ->label('Mandatory'),
            ])
            ->recordUrl(fn (NotificationTemplate $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Template')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('template_code')
                            ->label('Code')
                            ->badge()
                            ->color('primary')
                            ->copyable(),
                        TextEntry::make('name'),
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
                        TextEntry::make('priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'normal' => 'info',
                                'low' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('module')
                            ->placeholder('—'),
                    ]),

                Section::make('Content')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->schema([
                        TextEntry::make('subject')
                            ->placeholder('N/A (SMS)')
                            ->columnSpanFull(),
                        TextEntry::make('body')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Flags')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        IconEntry::make('is_mandatory')
                            ->label('Mandatory')
                            ->boolean(),
                        IconEntry::make('mask_sensitive_data')
                            ->label('Masks Sensitive Data')
                            ->boolean(),
                    ]),

                Section::make('Delivery History')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('logs')
                            ->label('')
                            ->schema([
                                TextEntry::make('recipient_identifier')
                                    ->label('Recipient'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'delivered' => 'success',
                                        'sent' => 'info',
                                        'pending', 'queued' => 'warning',
                                        'failed', 'bounced' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('channel')
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label('Sent At')
                                    ->dateTime(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationTemplates::route('/'),
            'create' => CreateNotificationTemplate::route('/create'),
            'view' => ViewNotificationTemplate::route('/{record}'),
            'edit' => EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
