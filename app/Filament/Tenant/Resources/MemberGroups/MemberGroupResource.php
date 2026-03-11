<?php

namespace App\Filament\Tenant\Resources\MemberGroups;

use App\Filament\Tenant\Concerns\BelongsToModule;
use App\Filament\Tenant\Resources\MemberGroups\Pages\CreateMemberGroup;
use App\Filament\Tenant\Resources\MemberGroups\Pages\EditMemberGroup;
use App\Filament\Tenant\Resources\MemberGroups\Pages\ListMemberGroups;
use App\Filament\Tenant\Resources\MemberGroups\Pages\ViewMemberGroup;
use App\Models\Tenant\MemberGroup;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MemberGroupResource extends Resource
{
    use BelongsToModule;

    protected static ?string $model = MemberGroup::class;

    protected static string $moduleKey = 'member_management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Group Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('group_name')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('group_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('branch_code')
                            ->maxLength(20),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'dissolved' => 'Dissolved',
                            ])
                            ->default('active')
                            ->required(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->fixed(),

                TextColumn::make('group_name')
                    ->label('Group Name')
                    ->searchable()
                    ->sortable()
                    ->fixed(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'dissolved' => 'danger',
                        default => 'gray',
                    })
                    ->fixed(),

                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->sortable(),

                TextColumn::make('branch_code')
                    ->label('Branch')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'dissolved' => 'Dissolved',
                    ]),
            ])
            ->recordUrl(fn (MemberGroup $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Group Information')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('group_code')
                            ->label('Code')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('group_name'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'dissolved' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('branch_code')
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Group Members')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        RepeatableEntry::make('members')
                            ->label('')
                            ->schema([
                                TextEntry::make('member_number'),
                                TextEntry::make('full_name'),
                                TextEntry::make('pivot.role')
                                    ->label('Role')
                                    ->badge(),
                                TextEntry::make('primary_phone'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberGroups::route('/'),
            'create' => CreateMemberGroup::route('/create'),
            'view' => ViewMemberGroup::route('/{record}'),
            'edit' => EditMemberGroup::route('/{record}/edit'),
        ];
    }
}
