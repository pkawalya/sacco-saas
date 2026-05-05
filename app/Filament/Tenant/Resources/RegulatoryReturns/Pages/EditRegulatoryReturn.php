<?php

namespace App\Filament\Tenant\Resources\RegulatoryReturns\Pages;

use App\Filament\Tenant\Resources\RegulatoryReturns\RegulatoryReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegulatoryReturn extends EditRecord
{
    protected static string $resource = RegulatoryReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
