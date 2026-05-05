<?php

namespace App\Filament\Tenant\Resources\CostAllocations\Pages;

use App\Filament\Tenant\Resources\CostAllocations\CostAllocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCostAllocation extends EditRecord
{
    protected static string $resource = CostAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
