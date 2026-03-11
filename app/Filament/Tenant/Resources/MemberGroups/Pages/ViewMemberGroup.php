<?php

namespace App\Filament\Tenant\Resources\MemberGroups\Pages;

use App\Filament\Tenant\Resources\MemberGroups\MemberGroupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMemberGroup extends ViewRecord
{
    protected static string $resource = MemberGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
