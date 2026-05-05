<?php

namespace App\Filament\Tenant\Resources\TellerShifts\Pages;

use App\Filament\Tenant\Resources\TellerShifts\TellerShiftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTellerShifts extends ListRecords
{
    protected static string $resource = TellerShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
