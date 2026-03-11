<?php

namespace App\Filament\Tenant\Resources\MemberGroups\Pages;

use App\Filament\Tenant\Resources\MemberGroups\MemberGroupResource;
use Filament\Resources\Pages\EditRecord;

class EditMemberGroup extends EditRecord
{
    protected static string $resource = MemberGroupResource::class;
}
