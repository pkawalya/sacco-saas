<?php

namespace App\Filament\Tenant\Resources\BaselReports\Pages;

use App\Filament\Tenant\Resources\BaselReports\BaselReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaselReports extends ListRecords
{
    protected static string $resource = BaselReportResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
