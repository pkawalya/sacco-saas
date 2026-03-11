<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('currency')
                            ->required()
                            ->default('UGX'),
                        TextInput::make('billing_cycle')
                            ->required()
                            ->default('monthly'),
                        TextInput::make('duration_months')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('HSMS Modules')
                    ->description('Select which SACCO modules are included in this plan. Tenants on this plan will only have access to the selected modules.')
                    ->schema([
                        Select::make('stage')
                            ->label('Institution Stage')
                            ->options([
                                1 => 'Stage 1 — Registered SACCO',
                                2 => 'Stage 2 — Regulated SACCO',
                                3 => 'Stage 3 — MFI / Credit Institution',
                                4 => 'Stage 4 — Microfinance Bank',
                            ])
                            ->default(1)
                            ->required()
                            ->helperText('Determines the baseline modules and features available.'),

                        CheckboxList::make('modules')
                            ->label('Active Modules')
                            ->options(
                                collect(config('modules', []))
                                    ->mapWithKeys(fn (array $module, string $key): array => [
                                        $key => $module['label'].' (Stage '.$module['stage'].')',
                                    ])
                                    ->toArray()
                            )
                            ->descriptions(
                                collect(config('modules', []))
                                    ->mapWithKeys(fn (array $module, string $key): array => [
                                        $key => $module['description'],
                                    ])
                                    ->toArray()
                            )
                            ->columns(2)
                            ->bulkToggleable()
                            ->helperText('Modules gated by stage are still selectable — use this when a tenant needs early access.'),
                    ]),

                Section::make('Configuration')
                    ->description('Flexible limits and features for this plan')
                    ->collapsible()
                    ->schema([
                        KeyValue::make('data')
                            ->keyLabel('Feature/Limit Name')
                            ->valueLabel('Value')
                            ->helperText('Add custom data like max_users, max_members, storage_limit, etc.'),
                    ]),

                Section::make('Status')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                        Toggle::make('is_custom')
                            ->label('Custom Plan')
                            ->default(false)
                            ->required(),
                        Toggle::make('support_custom_domain')
                            ->label('Support Custom Domain')
                            ->default(false)
                            ->required(),
                    ]),
            ]);
    }
}
