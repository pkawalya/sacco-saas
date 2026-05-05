<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\Concerns\HasTenantAdminActions;
use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    use HasTenantAdminActions;

    protected static string $resource = TenantResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getTenantAdminHeaderActions(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('super_admin')),
        ];
    }
}
