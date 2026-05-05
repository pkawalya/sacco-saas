<?php

namespace App\Filament\Tenant\Resources\SavingsAccounts\Pages;

use App\Filament\Tenant\Resources\SavingsAccounts\SavingsAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingsAccounts extends ListRecords
{
    protected static string $resource = SavingsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
