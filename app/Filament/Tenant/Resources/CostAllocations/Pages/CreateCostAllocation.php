<?php

namespace App\Filament\Tenant\Resources\CostAllocations\Pages;

use App\Filament\Tenant\Resources\CostAllocations\CostAllocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCostAllocation extends CreateRecord
{
    protected static string $resource = CostAllocationResource::class;
}
