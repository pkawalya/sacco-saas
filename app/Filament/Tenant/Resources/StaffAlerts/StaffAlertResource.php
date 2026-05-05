<?php

namespace App\Filament\Tenant\Resources\StaffAlerts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\StaffAlerts\Pages\ListStaffAlerts;
use App\Filament\Tenant\Resources\StaffAlerts\Pages\ViewStaffAlert;
use App\Models\Tenant\StaffAlert;
use App\Models\Tenant\User;
use BackedEnum;
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

class StaffAlertResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = StaffAlert::class;

    protected static string $moduleKey = 'notifications';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Staff Alerts';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alert_id')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('severity')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'info' => 'gray', 'warning' => 'warning', 'critical' => 'danger', default => 'gray',
                    }),
                TextColumn::make('recipient_name')->placeholder('—'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unread' => 'danger', 'read' => 'warning',
                        'acknowledged' => 'success', 'escalated' => 'gray', default => 'gray',
                    }),
                IconColumn::make('is_escalated')->boolean(),
                TextColumn::make('source_module')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('severity')->options(StaffAlert::SEVERITIES),
                SelectFilter::make('status')->options(StaffAlert::STATUSES),
            ])
            ->recordUrl(fn (StaffAlert $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Alert')
                ->icon(Heroicon::OutlinedBellAlert)
                ->columns(3)
                ->schema([
                    TextEntry::make('alert_id')->badge()->color('primary')->copyable(),
                    TextEntry::make('title'),
                    TextEntry::make('severity')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'critical' => 'danger', 'warning' => 'warning', default => 'gray',
                        }),
                    TextEntry::make('event_type')->badge(),
                    TextEntry::make('recipient_name')->placeholder('—'),
                    TextEntry::make('recipient_role')->placeholder('—'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('read_at')->dateTime()->placeholder('—'),
                    TextEntry::make('acknowledged_at')->dateTime()->placeholder('—'),
                    IconEntry::make('is_escalated')->boolean(),
                    TextEntry::make('escalation_tier'),
                    TextEntry::make('escalated_at')->dateTime()->placeholder('—'),
                    TextEntry::make('message')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffAlerts::route('/'),
            'view' => ViewStaffAlert::route('/{record}'),
        ];
    }
}
