<?php

namespace App\Filament\Tenant\Resources\CollectionsWorklists\Pages;

use App\Filament\Tenant\Resources\CollectionsWorklists\CollectionsWorklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectionsWorklists extends ListRecords
{
    protected static string $resource = CollectionsWorklistResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
