<?php

namespace App\Filament\Tenant\Resources\CostAllocations\Pages;

use App\Filament\Tenant\Resources\CostAllocations\CostAllocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCostAllocation extends ViewRecord
{
    protected static string $resource = CostAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
