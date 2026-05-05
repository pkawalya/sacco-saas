<?php

namespace App\Filament\Tenant\Resources\SavingsAccounts\Pages;

use App\Filament\Tenant\Resources\SavingsAccounts\SavingsAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavingsAccount extends EditRecord
{
    protected static string $resource = SavingsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
