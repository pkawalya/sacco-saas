<?php

namespace App\Filament\Tenant\Resources\TellerShifts;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\TellerShifts\Pages\ListTellerShifts;
use App\Filament\Tenant\Resources\TellerShifts\Pages\ViewTellerShift;
use App\Models\Tenant\TellerShift;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TellerShiftResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = TellerShift::class;

    protected static string $moduleKey = 'digital_channels';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_TELLER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Branch Operations';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shift_number')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('teller_name')->searchable()->sortable(),
                TextColumn::make('branch_code')->badge()->color('info'),
                TextColumn::make('opening_balance')->money('UGX'),
                TextColumn::make('total_deposits')->money('UGX'),
                TextColumn::make('total_withdrawals')->money('UGX'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success', 'closed' => 'gray', 'suspended' => 'danger', default => 'gray',
                    }),
                TextColumn::make('variance')->money('UGX')->placeholder('—')
                    ->color(fn (?string $state): string => $state && (float) $state !== 0.0 ? 'danger' : 'success'),
                TextColumn::make('opened_at')->dateTime()->sortable(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(TellerShift::STATUSES),
            ])
            ->recordUrl(fn (TellerShift $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Shift Details')
                ->icon(Heroicon::OutlinedBanknotes)
                ->columns(3)
                ->schema([
                    TextEntry::make('shift_number')->badge()->color('primary')->copyable(),
                    TextEntry::make('teller_name'),
                    TextEntry::make('branch_code')->badge(),
                    TextEntry::make('opening_balance')->money('UGX'),
                    TextEntry::make('closing_balance')->money('UGX')->placeholder('—'),
                    TextEntry::make('variance')->money('UGX')->placeholder('—'),
                    TextEntry::make('total_deposits')->money('UGX'),
                    TextEntry::make('total_withdrawals')->money('UGX'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('opened_at')->dateTime(),
                    TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('closing_notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTellerShifts::route('/'),
            'view' => ViewTellerShift::route('/{record}'),
        ];
    }
}
