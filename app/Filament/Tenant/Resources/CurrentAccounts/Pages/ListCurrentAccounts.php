<?php

namespace App\Filament\Tenant\Resources\CurrentAccounts\Pages;

use App\Filament\Tenant\Resources\CurrentAccounts\CurrentAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurrentAccounts extends ListRecords
{
    protected static string $resource = CurrentAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
