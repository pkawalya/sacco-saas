<?php

namespace App\Filament\Tenant\Resources\BaselReports;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\BaselReports\Pages\ListBaselReports;
use App\Filament\Tenant\Resources\BaselReports\Pages\ViewBaselReport;
use App\Models\Tenant\BaselReport;
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

class BaselReportResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = BaselReport::class;

    protected static string $moduleKey = 'mfb_upgrade';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Basel III';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report_ref')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('report_type')->badge(),
                TextColumn::make('reporting_period')->badge()->color('info'),
                TextColumn::make('car_ratio')->suffix('%')->label('CAR')
                    ->color(fn (?string $state): string => $state && (float) $state >= 12 ? 'success' : 'danger'),
                TextColumn::make('total_capital')->money('UGX'),
                TextColumn::make('risk_weighted_assets')->money('UGX')->label('RWA'),
                IconColumn::make('is_compliant')->boolean(),
                IconColumn::make('is_submitted')->boolean(),
            ])
            ->defaultSort('reporting_period', 'desc')
            ->filters([
                SelectFilter::make('report_type')->options(BaselReport::TYPES),
            ])
            ->recordUrl(fn (BaselReport $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Capital Adequacy')
                ->icon(Heroicon::OutlinedDocumentChartBar)
                ->columns(3)
                ->schema([
                    TextEntry::make('report_ref')->badge()->color('primary')->copyable(),
                    TextEntry::make('report_type')->badge(),
                    TextEntry::make('reporting_period')->badge(),
                    TextEntry::make('tier_1_capital')->money('UGX'),
                    TextEntry::make('tier_2_capital')->money('UGX'),
                    TextEntry::make('total_capital')->money('UGX'),
                    TextEntry::make('risk_weighted_assets')->money('UGX'),
                    TextEntry::make('car_ratio')->suffix('%')->label('CAR'),
                    TextEntry::make('minimum_car')->suffix('%')->label('Min CAR'),
                    TextEntry::make('hqla')->money('UGX')->label('HQLA'),
                    TextEntry::make('lcr_ratio')->suffix('%')->label('LCR'),
                    IconEntry::make('is_compliant')->boolean(),
                    IconEntry::make('is_submitted')->boolean(),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBaselReports::route('/'),
            'view' => ViewBaselReport::route('/{record}'),
        ];
    }
}
