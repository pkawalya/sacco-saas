<?php

namespace App\Filament\Tenant\Resources\AmlAlerts\Pages;

use App\Filament\Tenant\Resources\AmlAlerts\AmlAlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAmlAlerts extends ListRecords
{
    protected static string $resource = AmlAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
