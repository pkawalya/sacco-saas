<?php

namespace App\Filament\Tenant\Resources\RevenueSources;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\RevenueSources\Pages\CreateRevenueSource;
use App\Filament\Tenant\Resources\RevenueSources\Pages\EditRevenueSource;
use App\Filament\Tenant\Resources\RevenueSources\Pages\ListRevenueSources;
use App\Filament\Tenant\Resources\RevenueSources\Pages\ViewRevenueSource;
use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\RevenueSource;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
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

class RevenueSourceResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = RevenueSource::class;

    protected static string $moduleKey = 'revenue_expense';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Revenue & Expense';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'source_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Source Details')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(2)
                    ->schema([
                        TextInput::make('source_code')->required()->unique(ignoreRecord: true)->maxLength(30),
                        TextInput::make('source_name')->required()->maxLength(150),
                        Select::make('revenue_type')->options(RevenueSource::TYPES)->required(),
                        Select::make('recognition_basis')->options(RevenueSource::RECOGNITION_BASES)->default('accrual')->required(),
                        Select::make('frequency')->options(RevenueSource::FREQUENCIES)->default('one_time')->required(),
                        Select::make('gl_account_id')
                            ->label('GL Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->ofType('revenue')->pluck('account_name', 'id')->toArray())
                            ->searchable()->required(),
                        Textarea::make('description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Withholding Tax')
                    ->columns(3)
                    ->schema([
                        Toggle::make('wht_applicable')->label('WHT Applicable')->reactive(),
                        TextInput::make('wht_rate')
                            ->label('WHT Rate (%)')
                            ->numeric()->default(0)->suffix('%')
                            ->maxValue(100),
                        Select::make('wht_account_id')
                            ->label('WHT Payable Account')
                            ->options(fn (): array => ChartOfAccount::query()->postable()->ofType('liability')->pluck('account_name', 'id')->toArray())
                            ->searchable()->nullable(),
                    ]),

                Section::make('Controls')
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_active')->label('Active')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_code')->label('Code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('source_name')->label('Name')->searchable()->sortable(),
                TextColumn::make('revenue_type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'interest' => 'success', 'fee' => 'info', 'commission' => 'warning',
                        'penalty' => 'danger', 'investment' => 'primary', default => 'gray',
                    }),
                TextColumn::make('recognition_basis')->badge()->color('gray'),
                TextColumn::make('glAccount.account_code')->label('GL Code'),
                TextColumn::make('wht_rate')->label('WHT %')->suffix('%')->alignCenter(),
                IconColumn::make('wht_applicable')->label('WHT')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('source_code')
            ->filters([
                SelectFilter::make('revenue_type')->options(RevenueSource::TYPES),
                SelectFilter::make('recognition_basis')->options(RevenueSource::RECOGNITION_BASES),
                TernaryFilter::make('wht_applicable')->label('WHT Applicable'),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordUrl(fn (RevenueSource $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Revenue Source')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('source_code')->badge()->color('primary')->copyable(),
                        TextEntry::make('source_name'),
                        TextEntry::make('revenue_type')->badge(),
                        TextEntry::make('recognition_basis')->badge()->color('gray'),
                        TextEntry::make('frequency')->badge()->color('gray'),
                        TextEntry::make('glAccount.account_name')->label('GL Account'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Withholding Tax')
                    ->icon(Heroicon::OutlinedPercent)
                    ->columns(3)
                    ->schema([
                        IconEntry::make('wht_applicable')->label('WHT Applicable')->boolean(),
                        TextEntry::make('wht_rate')->label('WHT Rate')->suffix('%'),
                        TextEntry::make('whtAccount.account_name')->label('WHT Account')->placeholder('—'),
                    ]),
                Section::make('Flags')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(1)
                    ->schema([
                        IconEntry::make('is_active')->label('Active')->boolean(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRevenueSources::route('/'),
            'create' => CreateRevenueSource::route('/create'),
            'view' => ViewRevenueSource::route('/{record}'),
            'edit' => EditRevenueSource::route('/{record}/edit'),
        ];
    }
}
