<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Filament\Resources\Tenants\TenantResource;
use App\Jobs\Central\CreateTenantAdminFromCentral;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantDeletionService;
use App\Services\TenantProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID / Subdomain')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Business Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_provisioned')
                    ->label('Provisioned')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (Model $record): string => TenantResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
                Action::make('provision')
                    ->label('Provision')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (Tenant $record) => $record->is_provisioned)
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->action(function (Tenant $record, TenantProvisioningService $service) {
                        $service->provisionManual($record);
                        Notification::make()
                            ->title('Provisioning started')
                            ->success()
                            ->send();
                    }),
                Action::make('createAdmin')
                    ->label('Create Admin')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->color('info')
                    ->hidden(fn (Tenant $record) => ! $record->is_provisioned)
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->modalHeading(fn (Tenant $record) => "Create Admin — {$record->name}")
                    ->modalDescription('Create a new admin user who can log in to the tenant panel.')
                    ->modalWidth('lg')
                    ->form([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required(),
                        Select::make('role')
                            ->label('Role')
                            ->options(TenantUser::ROLES)
                            ->default(TenantUser::ROLE_ADMIN)
                            ->required(),
                        TextInput::make('password')
                            ->label('Initial Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->default(fn () => Str::password(12))
                            ->helperText('User will be prompted to change this on first login.')
                            ->suffixAction(
                                Action::make('generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(fn ($set) => $set('password', Str::password(12)))
                            ),
                        Toggle::make('send_email')
                            ->label('Send login credentials via email')
                            ->default(true),
                    ])
                    ->action(function (Tenant $record, array $data): void {
                        CreateTenantAdminFromCentral::dispatch($record, $data);
                        Notification::make()
                            ->title('Admin user creation queued')
                            ->body("Account for {$data['email']} is being created in {$record->name}.")
                            ->success()
                            ->send();
                    }),
                Action::make('purge')
                    ->label('Purge')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete the tenant database, storage, and record. This action cannot be undone.')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->action(function (Tenant $record, TenantDeletionService $service) {
                        $service->deleteTenant($record);
                        Notification::make()
                            ->title('Tenant purge process started')
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => auth()->user()->hasRole('super_admin')),
            ]);
    }
}
