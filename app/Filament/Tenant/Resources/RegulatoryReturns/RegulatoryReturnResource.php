<?php

namespace App\Filament\Tenant\Resources\RegulatoryReturns;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\RegulatoryReturns\Pages\CreateRegulatoryReturn;
use App\Filament\Tenant\Resources\RegulatoryReturns\Pages\EditRegulatoryReturn;
use App\Filament\Tenant\Resources\RegulatoryReturns\Pages\ListRegulatoryReturns;
use App\Filament\Tenant\Resources\RegulatoryReturns\Pages\ViewRegulatoryReturn;
use App\Models\Tenant\RegulatoryReturn;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
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

class RegulatoryReturnResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = RegulatoryReturn::class;

    protected static string $moduleKey = 'regulatory_compliance';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Regulatory Compliance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Returns';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Return Details')
                ->icon(Heroicon::OutlinedDocumentText)
                ->columns(2)
                ->schema([
                    TextInput::make('return_name')->required()->maxLength(200),
                    Select::make('return_type')->options(RegulatoryReturn::TYPES)->required(),
                    Select::make('period')->options(RegulatoryReturn::PERIODS)->required(),
                    TextInput::make('fiscal_year')->numeric()->required()->default(now()->year),
                    TextInput::make('period_number')->numeric()->nullable()->minValue(1)->maxValue(12),
                    DatePicker::make('period_start')->required(),
                    DatePicker::make('period_end')->required(),
                    DatePicker::make('due_date')->required(),
                    TextInput::make('reminder_days_before')->numeric()->default(7),
                    Select::make('status')->options(RegulatoryReturn::STATUSES)->default('pending'),
                    Textarea::make('notes')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('return_name')->searchable()->sortable()->limit(40),
                TextColumn::make('return_type')->badge()
                    ->formatStateUsing(fn (string $s): string => RegulatoryReturn::TYPES[$s] ?? $s)
                    ->color('gray'),
                TextColumn::make('period')->badge()->color('info'),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('days_until_due')->label('Days Left')
                    ->state(fn (RegulatoryReturn $r): int => $r->days_until_due)
                    ->badge()->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger', $state <= 7 => 'warning', default => 'success',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray', 'in_progress' => 'info', 'submitted' => 'warning',
                        'accepted' => 'success', 'rejected' => 'danger', 'overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('return_type')->options(RegulatoryReturn::TYPES),
                SelectFilter::make('status')->options(RegulatoryReturn::STATUSES),
            ])
            ->recordUrl(fn (RegulatoryReturn $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Return')
                ->icon(Heroicon::OutlinedDocumentText)
                ->columns(3)
                ->schema([
                    TextEntry::make('return_code')->badge()->color('primary')->copyable(),
                    TextEntry::make('return_name'),
                    TextEntry::make('return_type')->badge()
                        ->formatStateUsing(fn (string $s): string => RegulatoryReturn::TYPES[$s] ?? $s),
                    TextEntry::make('period')->badge(),
                    TextEntry::make('fiscal_year'),
                    TextEntry::make('period_number')->placeholder('—'),
                    TextEntry::make('period_start')->date(),
                    TextEntry::make('period_end')->date(),
                    TextEntry::make('due_date')->date(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('filed_date')->date()->placeholder('—'),
                    TextEntry::make('filing_reference')->placeholder('—'),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegulatoryReturns::route('/'),
            'create' => CreateRegulatoryReturn::route('/create'),
            'view' => ViewRegulatoryReturn::route('/{record}'),
            'edit' => EditRegulatoryReturn::route('/{record}/edit'),
        ];
    }
}
