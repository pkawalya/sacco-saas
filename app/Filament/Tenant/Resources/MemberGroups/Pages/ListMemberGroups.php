<?php

namespace App\Filament\Tenant\Resources\MemberGroups\Pages;

use App\Filament\Tenant\Resources\MemberGroups\MemberGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberGroups extends ListRecords
{
    protected static string $resource = MemberGroupResource::class;
}
