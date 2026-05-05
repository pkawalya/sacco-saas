<?php

namespace App\Filament\Tenant\Resources\CostAllocations\Pages;

use App\Filament\Tenant\Resources\CostAllocations\CostAllocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCostAllocations extends ListRecords
{
    protected static string $resource = CostAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
