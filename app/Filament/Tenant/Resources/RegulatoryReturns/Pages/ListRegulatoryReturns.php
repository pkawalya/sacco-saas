<?php

namespace App\Filament\Tenant\Resources\RegulatoryReturns\Pages;

use App\Filament\Tenant\Resources\RegulatoryReturns\RegulatoryReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegulatoryReturns extends ListRecords
{
    protected static string $resource = RegulatoryReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
