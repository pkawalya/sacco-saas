<?php

namespace App\Filament\Tenant\Resources\StaffUsers\Pages;

use App\Filament\Tenant\Resources\StaffUsers\StaffUserResource;
use Filament\Resources\Pages\EditRecord;

class EditStaffUser extends EditRecord
{
    protected static string $resource = StaffUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
