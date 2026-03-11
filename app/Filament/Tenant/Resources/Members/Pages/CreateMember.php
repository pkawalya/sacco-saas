<?php

namespace App\Filament\Tenant\Resources\Members\Pages;

use App\Filament\Tenant\Resources\Members\MemberResource;
use App\Services\MemberNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['member_number'] = MemberNumberGenerator::generate($data['branch_code'] ?? null);
        $data['registered_by'] = auth()->id();

        return $data;
    }
}
