<?php

namespace App\Filament\Tenant\Resources\RegulatoryReturns\Pages;

use App\Filament\Tenant\Resources\RegulatoryReturns\RegulatoryReturnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRegulatoryReturn extends ViewRecord
{
    protected static string $resource = RegulatoryReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
