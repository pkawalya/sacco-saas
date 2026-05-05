<?php

namespace App\Filament\Tenant\Resources\Agents;

use App\Filament\Tenant\Concerns\BelongsToRole;
use App\Filament\Tenant\Resources\Agents\Pages\ListAgents;
use App\Filament\Tenant\Resources\Agents\Pages\ViewAgent;
use App\Models\Tenant\Agent;
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

class AgentResource extends Resource
{
    use BelongsToRole;

    protected static ?string $model = Agent::class;

    protected static string $moduleKey = 'digital_channels';

    protected static array $allowedRoles = [User::ROLE_ADMIN, User::ROLE_MANAGER];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Agent Banking';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent_code')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('agent_name')->searchable()->sortable(),
                TextColumn::make('business_name')->placeholder('—'),
                TextColumn::make('float_balance')->money('UGX')->sortable(),
                TextColumn::make('total_commission_earned')->money('UGX')->label('Commission'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'suspended' => 'warning', 'deactivated' => 'danger', default => 'gray',
                    }),
            ])
            ->defaultSort('agent_name')
            ->filters([
                SelectFilter::make('status')->options(Agent::STATUSES),
            ])
            ->recordUrl(fn (Agent $r): string => static::getUrl('view', ['record' => $r]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Agent')
                ->icon(Heroicon::OutlinedUserGroup)
                ->columns(3)
                ->schema([
                    TextEntry::make('agent_code')->badge()->color('primary')->copyable(),
                    TextEntry::make('agent_name'),
                    TextEntry::make('business_name')->placeholder('—'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('branch_code')->badge()->placeholder('—'),
                    TextEntry::make('float_balance')->money('UGX'),
                    TextEntry::make('float_limit')->money('UGX'),
                    TextEntry::make('commission_rate')->suffix('%'),
                    TextEntry::make('total_commission_earned')->money('UGX'),
                    TextEntry::make('status')->badge(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgents::route('/'),
            'view' => ViewAgent::route('/{record}'),
        ];
    }
}
