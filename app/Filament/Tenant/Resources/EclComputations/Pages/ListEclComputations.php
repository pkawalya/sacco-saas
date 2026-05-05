<?php

namespace App\Filament\Tenant\Resources\EclComputations\Pages;

use App\Filament\Tenant\Resources\EclComputations\EclComputationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEclComputations extends ListRecords
{
    protected static string $resource = EclComputationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
