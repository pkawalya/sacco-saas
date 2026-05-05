<?php

namespace App\Filament\Tenant\Resources\StaffAlerts\Pages;

use App\Filament\Tenant\Resources\StaffAlerts\StaffAlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffAlerts extends ListRecords
{
    protected static string $resource = StaffAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
