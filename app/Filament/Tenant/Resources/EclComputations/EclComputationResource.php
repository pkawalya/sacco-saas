<?php

namespace App\Filament\Tenant\Resources\EclComputations;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\EclComputations\Pages\ListEclComputations;
use App\Filament\Tenant\Resources\EclComputations\Pages\ViewEclComputation;
use App\Models\Tenant\EclComputation;
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
use Filament\Tables\Table;

class EclComputationResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = EclComputation::class;

    protected static string $moduleKey = 'advanced_analytics';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'IFRS 9';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('computation_period')->sortable()->badge()->color('primary'),
                TextColumn::make('total_ead')->money('UGX')->label('Total EAD'),
                TextColumn::make('total_ecl')->money('UGX')->label('Total ECL'),
                TextColumn::make('coverage_ratio')->suffix('%')->label('Coverage'),
                TextColumn::make('stage_1_count')->label('S1'),
                TextColumn::make('stage_2_count')->label('S2'),
                TextColumn::make('stage_3_count')->label('S3'),
                IconColumn::make('is_posted')->boolean(),
            ])
            ->defaultSort('computation_period', 'desc')
            ->recordUrl(fn (EclComputation $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('ECL Computation')
                ->icon(Heroicon::OutlinedCalculator)
                ->columns(3)
                ->schema([
                    TextEntry::make('computation_period')->badge()->color('primary'),
                    TextEntry::make('computation_date')->date(),
                    TextEntry::make('total_ead')->money('UGX'),
                    TextEntry::make('total_ecl')->money('UGX'),
                    TextEntry::make('provision_amount')->money('UGX'),
                    TextEntry::make('coverage_ratio')->suffix('%'),
                    TextEntry::make('stage_1_count')->label('Stage 1 Loans'),
                    TextEntry::make('stage_1_ecl')->money('UGX')->label('Stage 1 ECL'),
                    TextEntry::make('stage_2_count')->label('Stage 2 Loans'),
                    TextEntry::make('stage_2_ecl')->money('UGX')->label('Stage 2 ECL'),
                    TextEntry::make('stage_3_count')->label('Stage 3 Loans'),
                    TextEntry::make('stage_3_ecl')->money('UGX')->label('Stage 3 ECL'),
                    IconEntry::make('is_posted')->boolean(),
                    TextEntry::make('journal_reference')->placeholder('—'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEclComputations::route('/'),
            'view' => ViewEclComputation::route('/{record}'),
        ];
    }
}
