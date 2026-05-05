<?php

namespace App\Filament\Tenant\Resources\SavingsAccounts\Pages;

use App\Filament\Tenant\Resources\SavingsAccounts\SavingsAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSavingsAccount extends ViewRecord
{
    protected static string $resource = SavingsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
