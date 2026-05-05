<?php

namespace App\Filament\Tenant\Resources\PtpRecords\Pages;

use App\Filament\Tenant\Resources\PtpRecords\PtpRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPtpRecords extends ListRecords
{
    protected static string $resource = PtpRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
