<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Models\Central\Tenant;
use App\Services\TenantDeletionService;
use App\Services\TenantProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordActions([
                EditAction::make(),
                Action::make('provision')
                    ->label('Provision')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (Tenant $record) => $record->is_provisioned)
                    ->action(function (Tenant $record, TenantProvisioningService $service) {
                        $service->provisionManual($record);
                        Notification::make()
                            ->title('Provisioning started')
                            ->success()
                            ->send();
                    }),
                Action::make('purge')
                    ->label('Purge')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete the tenant database, storage, and record. This action cannot be undone.')
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
                ]),
            ]);
    }
}
