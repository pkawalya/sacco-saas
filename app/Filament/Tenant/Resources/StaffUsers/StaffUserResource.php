<?php

namespace App\Filament\Tenant\Resources\StaffUsers;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\StaffUsers\Pages\CreateStaffUser;
use App\Filament\Tenant\Resources\StaffUsers\Pages\EditStaffUser;
use App\Filament\Tenant\Resources\StaffUsers\Pages\ListStaffUsers;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffUserResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = User::class;

    protected static array $allowedRoles = [User::ROLE_ADMIN];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Staff & Users';

    protected static ?string $modelLabel = 'Staff User';

    protected static ?string $pluralModelLabel = 'Staff & Users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Staff Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('role')
                        ->options(User::ROLES)
                        ->required()
                        ->default(User::ROLE_STAFF)
                        ->native(false)
                        ->helperText('Determines what this user can access in the system.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive users cannot log in.'),
                ]),

            Section::make('Password')
                ->columns(2)
                ->description('Leave blank to keep the existing password when editing.')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->confirmed(),

                    TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->requiredWith('password'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                BadgeColumn::make('role')
                    ->colors([
                        'danger' => User::ROLE_ADMIN,
                        'warning' => User::ROLE_MANAGER,
                        'info' => User::ROLE_STAFF,
                        'gray' => User::ROLE_TELLER,
                    ])
                    ->formatStateUsing(fn (string $state): string => User::ROLES[$state] ?? ucfirst($state)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')
                    ->options(User::ROLES),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())), // Can't delete yourself
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffUsers::route('/'),
            'create' => CreateStaffUser::route('/create'),
            'edit' => EditStaffUser::route('/{record}/edit'),
        ];
    }
}
