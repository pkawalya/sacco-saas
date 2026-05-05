<?php

namespace App\Filament\Tenant\Resources\CostAllocations;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\CostAllocations\Pages\CreateCostAllocation;
use App\Filament\Tenant\Resources\CostAllocations\Pages\EditCostAllocation;
use App\Filament\Tenant\Resources\CostAllocations\Pages\ListCostAllocations;
use App\Filament\Tenant\Resources\CostAllocations\Pages\ViewCostAllocation;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\CostAllocation;
use App\Models\Tenant\CostCentre;
use App\Models\Tenant\User;
use BackedEnum;
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

class CostAllocationResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = CostAllocation::class;

    protected static string $moduleKey = 'cost_centres';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsPointingOut;

    protected static string|\UnitEnum|null $navigationGroup = 'Cost Centres';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Allocations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allocation Details')
                    ->icon(Heroicon::OutlinedArrowsPointingOut)
                    ->columns(2)
                    ->schema([
                        Select::make('cost_centre_id')
                            ->label('Cost Centre')
                            ->options(fn (): array => CostCentre::query()->active()->orderBy('code')
                                ->get()->mapWithKeys(fn (CostCentre $cc): array => [$cc->id => "{$cc->code} – {$cc->name}"])->toArray())
                            ->searchable()->required(),
                        Select::make('gl_account_id')
                            ->label('GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->pluck('account_name', 'id')->toArray())
                            ->searchable()->required(),
                        TextInput::make('fiscal_year')->numeric()->required()->default(now()->year),
                        TextInput::make('period_month')->numeric()->nullable()->minValue(1)->maxValue(12)
                            ->helperText('Leave empty for annual'),
                    ]),

                Section::make('Amounts')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(2)
                    ->schema([
                        TextInput::make('allocated_amount')->numeric()->default(0)->prefix('UGX'),
                        TextInput::make('actual_amount')->numeric()->default(0)->prefix('UGX'),
                    ]),

                Section::make('Allocation Method')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(2)
                    ->schema([
                        Select::make('allocation_method')->options(CostAllocation::METHODS)->default('direct')->required(),
                        TextInput::make('allocation_percentage')->numeric()->default(100)->suffix('%')->maxValue(100),
                        Select::make('status')->options(CostAllocation::STATUSES)->default('active')->required(),
                    ]),

                Section::make('Charge-Back')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('chargeback_from_id')
                            ->label('Charge-Back From')
                            ->options(fn (): array => CostCentre::query()->active()->orderBy('code')
                                ->get()->mapWithKeys(fn (CostCentre $cc): array => [$cc->id => "{$cc->code} – {$cc->name}"])->toArray())
                            ->searchable()->nullable(),
                        TextInput::make('transfer_price')->numeric()->default(0)->prefix('UGX'),
                        Textarea::make('chargeback_description')->rows(2)->nullable()->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('costCentre.code')->label('CC Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('costCentre.name')->label('Cost Centre')->searchable(),
                TextColumn::make('glAccount.account_code')->label('GL Code')->sortable(),
                TextColumn::make('allocation_method')->label('Method')->badge()->color('gray'),
                TextColumn::make('fiscal_year')->sortable(),
                TextColumn::make('period_month')->label('Month')->placeholder('Annual'),
                TextColumn::make('allocated_amount')->money('UGX')->sortable(),
                TextColumn::make('actual_amount')->money('UGX'),
                TextColumn::make('variance')->label('Variance')->money('UGX')
                    ->state(fn (CostAllocation $r): float => $r->variance)
                    ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'frozen' => 'warning', 'closed' => 'gray', default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('allocation_method')->options(CostAllocation::METHODS),
                SelectFilter::make('status')->options(CostAllocation::STATUSES),
                SelectFilter::make('fiscal_year')
                    ->options(fn (): array => CostAllocation::query()->distinct()->pluck('fiscal_year', 'fiscal_year')->toArray()),
            ])
            ->recordUrl(fn (CostAllocation $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Allocation')
                    ->icon(Heroicon::OutlinedArrowsPointingOut)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('costCentre.code')->label('Cost Centre')->badge()->color('primary'),
                        TextEntry::make('costCentre.name'),
                        TextEntry::make('glAccount.account_name')->label('GL Account'),
                        TextEntry::make('allocation_method')->badge()->color('gray'),
                        TextEntry::make('allocation_percentage')->suffix('%'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success', 'frozen' => 'warning', 'closed' => 'gray', default => 'gray',
                            }),
                        TextEntry::make('fiscal_year'),
                        TextEntry::make('period_month')->placeholder('Annual'),
                    ]),
                Section::make('Amounts')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('allocated_amount')->money('UGX'),
                        TextEntry::make('actual_amount')->money('UGX'),
                        TextEntry::make('variance')->money('UGX')
                            ->state(fn (CostAllocation $r): float => $r->variance)
                            ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                    ]),
                Section::make('Charge-Back')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('chargebackFrom.code')->label('From CC')->badge()->placeholder('—'),
                        TextEntry::make('transfer_price')->money('UGX'),
                        TextEntry::make('chargeback_description')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCostAllocations::route('/'),
            'create' => CreateCostAllocation::route('/create'),
            'view' => ViewCostAllocation::route('/{record}'),
            'edit' => EditCostAllocation::route('/{record}/edit'),
        ];
    }
}
