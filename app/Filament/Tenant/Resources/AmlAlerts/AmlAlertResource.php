<?php

namespace App\Filament\Tenant\Resources\AmlAlerts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\AmlAlerts\Pages\ListAmlAlerts;
use App\Filament\Tenant\Resources\AmlAlerts\Pages\ViewAmlAlert;
use App\Models\Tenant\AmlAlert;
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

class AmlAlertResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = AmlAlert::class;

    protected static string $moduleKey = 'regulatory_compliance';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|\UnitEnum|null $navigationGroup = 'Regulatory Compliance';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'AML Alerts';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alert_id')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('member_name')->searchable()->sortable(),
                TextColumn::make('rule_triggered')->badge()
                    ->formatStateUsing(fn (string $s): string => AmlAlert::RULES[$s] ?? $s)
                    ->color('gray'),
                TextColumn::make('severity')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'danger', default => 'gray',
                    }),
                TextColumn::make('transaction_amount')->money('UGX')->placeholder('—'),
                TextColumn::make('risk_score')->badge()
                    ->color(fn (int $state): string => $state >= 70 ? 'danger' : ($state >= 40 ? 'warning' : 'success')),
                IconColumn::make('is_escalated')->boolean(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray', 'under_review' => 'info', 'escalated' => 'warning',
                        'cleared' => 'success', 'str_filed' => 'danger', default => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('severity')->options(AmlAlert::SEVERITIES),
                SelectFilter::make('status')->options(AmlAlert::STATUSES),
                SelectFilter::make('rule_triggered')->label('Rule')->options(AmlAlert::RULES),
            ])
            ->recordUrl(fn (AmlAlert $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Alert')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->columns(3)
                ->schema([
                    TextEntry::make('alert_id')->badge()->color('primary')->copyable(),
                    TextEntry::make('member_name'),
                    TextEntry::make('account_number')->placeholder('—'),
                    TextEntry::make('rule_triggered')->badge(),
                    TextEntry::make('severity')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'high', 'critical' => 'danger', 'medium' => 'warning', default => 'gray',
                        }),
                    TextEntry::make('risk_score')->badge(),
                    TextEntry::make('transaction_amount')->money('UGX')->placeholder('—'),
                    TextEntry::make('cumulative_amount')->money('UGX')->placeholder('—'),
                    TextEntry::make('status')->badge(),
                    IconEntry::make('is_escalated')->boolean(),
                    TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('review_notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmlAlerts::route('/'),
            'view' => ViewAmlAlert::route('/{record}'),
        ];
    }
}
