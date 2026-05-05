<?php

namespace App\Filament\Tenant\Resources\LendingGroups\Pages;

use App\Filament\Tenant\Resources\LendingGroups\LendingGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLendingGroups extends ListRecords
{
    protected static string $resource = LendingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
