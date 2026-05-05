<?php

namespace App\Filament\Tenant\Resources\StaffUsers\Pages;

use App\Filament\Tenant\Resources\StaffUsers\StaffUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffUsers extends ListRecords
{
    protected static string $resource = StaffUserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add Staff Member')];
    }
}
