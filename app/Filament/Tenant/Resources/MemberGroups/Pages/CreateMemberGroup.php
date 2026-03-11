<?php

namespace App\Filament\Tenant\Resources\MemberGroups\Pages;

use App\Filament\Tenant\Resources\MemberGroups\MemberGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMemberGroup extends CreateRecord
{
    protected static string $resource = MemberGroupResource::class;
}
