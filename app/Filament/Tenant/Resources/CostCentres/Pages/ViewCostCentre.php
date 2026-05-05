<?php

namespace App\Filament\Tenant\Resources\CostCentres\Pages;

use App\Filament\Tenant\Resources\CostCentres\CostCentreResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCostCentre extends ViewRecord
{
    protected static string $resource = CostCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
