<?php

namespace App\Filament\Tenant\Resources\CostCentres\Pages;

use App\Filament\Tenant\Resources\CostCentres\CostCentreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCostCentre extends EditRecord
{
    protected static string $resource = CostCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
