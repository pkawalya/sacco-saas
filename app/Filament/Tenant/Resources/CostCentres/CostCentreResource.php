<?php

namespace App\Filament\Tenant\Resources\CostCentres;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\CostCentres\Pages\CreateCostCentre;
use App\Filament\Tenant\Resources\CostCentres\Pages\EditCostCentre;
use App\Filament\Tenant\Resources\CostCentres\Pages\ListCostCentres;
use App\Filament\Tenant\Resources\CostCentres\Pages\ViewCostCentre;
use App\Models\Tenant\CostCentre;
use App\Models\Tenant\User;
use BackedEnum;
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

class CostCentreResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = CostCentre::class;

    protected static string $moduleKey = 'cost_centres';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Cost Centres';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cost Centre Details')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30)
                            ->placeholder('e.g. DIV-FIN'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(150),

                        Select::make('level')
                            ->options(CostCentre::LEVELS)
                            ->required()
                            ->reactive(),

                        Select::make('parent_id')
                            ->label('Parent Cost Centre')
                            ->options(fn (): array => CostCentre::query()
                                ->active()
                                ->orderBy('level')
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (CostCentre $cc): array => [
                                    $cc->id => "[{$cc->level_label}] {$cc->code} – {$cc->name}",
                                ])
                                ->toArray())
                            ->searchable()
                            ->nullable(),

                        TextInput::make('manager_name')
                            ->maxLength(150)
                            ->nullable(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()

                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('level')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => CostCentre::LEVELS[$state] ?? 'Unknown')
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'gray', default => 'gray',
                    }),

                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('— (Root)'),

                TextColumn::make('manager_name')
                    ->label('Manager')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('children_count')
                    ->label('Sub-units')
                    ->counts('children')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code')
            ->filters([
                SelectFilter::make('level')
                    ->options(CostCentre::LEVELS),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(fn (): array => CostCentre::query()
                        ->roots()
                        ->pluck('name', 'id')
                        ->toArray())
                    ->placeholder('All'),
            ])
            ->recordUrl(fn (CostCentre $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Cost Centre')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')->badge()->color('primary')->copyable(),
                        TextEntry::make('name'),
                        TextEntry::make('level')
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => CostCentre::LEVELS[$state] ?? 'Unknown')
                            ->color(fn (int $state): string => match ($state) {
                                1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'gray', default => 'gray',
                            }),
                        TextEntry::make('path')->label('Full Hierarchy Path'),
                        TextEntry::make('parent.name')->label('Parent')->placeholder('— (Root)'),
                        TextEntry::make('manager_name')->placeholder('—'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ]),

                Section::make('Status')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('deactivated_at')->dateTime()->placeholder('—'),
                        TextEntry::make('deactivation_reason')->placeholder('—'),
                    ]),

                Section::make('Children')
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('children')
                            ->label('')
                            ->schema([
                                TextEntry::make('code')->badge()->color('primary'),
                                TextEntry::make('name'),
                                TextEntry::make('level')
                                    ->badge()
                                    ->formatStateUsing(fn (int $state): string => CostCentre::LEVELS[$state] ?? ''),
                                IconEntry::make('is_active')->boolean(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCostCentres::route('/'),
            'create' => CreateCostCentre::route('/create'),
            'view' => ViewCostCentre::route('/{record}'),
            'edit' => EditCostCentre::route('/{record}/edit'),
        ];
    }
}
