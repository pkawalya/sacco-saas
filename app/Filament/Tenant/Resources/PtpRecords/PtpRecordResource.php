<?php

namespace App\Filament\Tenant\Resources\PtpRecords;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\PtpRecords\Pages\ListPtpRecords;
use App\Filament\Tenant\Resources\PtpRecords\Pages\ViewPtpRecord;
use App\Models\Tenant\PtpRecord;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PtpRecordResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = PtpRecord::class;

    protected static string $moduleKey = 'collections_engine';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'PTPs';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_number')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('promised_amount')->money('UGX')->sortable(),
                TextColumn::make('promised_date')->date()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kept' => 'success', 'broken' => 'danger',
                        'partial' => 'warning', default => 'gray',
                    }),
                IconColumn::make('is_broken')->label('Broken')->boolean(),
                TextColumn::make('actual_amount_paid')->money('UGX')->placeholder('—'),
                TextColumn::make('officer_name')->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('promised_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(PtpRecord::STATUSES),
                TernaryFilter::make('is_broken')->label('Broken'),
            ])
            ->recordUrl(fn (PtpRecord $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Promise to Pay')
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('loan_number')->badge()->color('primary'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'kept' => 'success', 'broken' => 'danger',
                                'partial' => 'warning', default => 'gray',
                            }),
                        IconEntry::make('is_broken')->label('Broken Flag')->boolean(),
                        TextEntry::make('promised_amount')->money('UGX'),
                        TextEntry::make('promised_date')->date(),
                        TextEntry::make('actual_amount_paid')->money('UGX'),
                        TextEntry::make('actual_payment_date')->date()->placeholder('—'),
                        TextEntry::make('officer_name')->placeholder('—'),
                        TextEntry::make('broken_flagged_at')->dateTime()->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPtpRecords::route('/'),
            'view' => ViewPtpRecord::route('/{record}'),
        ];
    }
}
