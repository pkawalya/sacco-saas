<?php

namespace App\Filament\Tenant\Resources\EscalationChains;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\EscalationChains\Pages\CreateEscalationChain;
use App\Filament\Tenant\Resources\EscalationChains\Pages\EditEscalationChain;
use App\Filament\Tenant\Resources\EscalationChains\Pages\ListEscalationChains;
use App\Models\Tenant\EscalationChain;
use App\Models\Tenant\NotificationPreference;
use App\Models\Tenant\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EscalationChainResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = EscalationChain::class;

    protected static string $moduleKey = 'notifications';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Escalation Chains';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Escalation Rule')
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->columns(2)
                ->schema([
                    TextInput::make('alert_type')->required()->maxLength(50),
                    TextInput::make('tier')->numeric()->required()->minValue(1)->maxValue(5),
                    Select::make('recipient_role')->options(EscalationChain::ROLES)->required(),
                    TextInput::make('escalate_after_minutes')
                        ->label('Escalate After (minutes)')
                        ->numeric()->required()->default(60),
                    Select::make('notification_channel')
                        ->options(NotificationPreference::CHANNELS)
                        ->default('email'),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alert_type')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('tier')->sortable()->badge()->color('info'),
                TextColumn::make('recipient_role')->badge()
                    ->formatStateUsing(fn (string $s): string => EscalationChain::ROLES[$s] ?? $s),
                TextColumn::make('escalate_after_minutes')->label('After (min)')->sortable(),
                TextColumn::make('notification_channel')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('alert_type');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEscalationChains::route('/'),
            'create' => CreateEscalationChain::route('/create'),
            'edit' => EditEscalationChain::route('/{record}/edit'),
        ];
    }
}
