<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Central\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Admin Users';

    protected static ?string $modelLabel = 'Admin User';

    protected static ?string $pluralModelLabel = 'Admin Users';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Details')
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

                    Select::make('roles')
                        ->label('Role')
                        ->relationship('roles', 'name')
                        ->options(fn (): array => Role::query()->pluck('name', 'id')->toArray())
                        ->multiple()
                        ->preload()
                        ->native(false)
                        ->helperText('Roles determine what this user can access.'),

                    Toggle::make('is_approved')
                        ->label('Approved')
                        ->helperText('Unapproved users cannot log in.')
                        ->default(false),
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

            Section::make('Account Status')
                ->columns(2)
                ->collapsible()
                ->schema([
                    DateTimePicker::make('approved_at')
                        ->label('Approved At')
                        ->disabled(),

                    DateTimePicker::make('last_login_at')
                        ->label('Last Login')
                        ->disabled(),

                    TextInput::make('last_login_ip')
                        ->label('Last Login IP')
                        ->disabled(),

                    Toggle::make('must_change_password')
                        ->label('Must Change Password')
                        ->helperText('Force the user to change their password on next login.'),
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

                BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->separator(','),

                IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('Approval Status')
                    ->options([
                        '1' => 'Approved',
                        '0' => 'Pending Approval',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
