<?php

namespace App\Filament\Tenant\Resources\RevenueSources\Pages;

use App\Filament\Tenant\Resources\RevenueSources\RevenueSourceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRevenueSource extends ViewRecord
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
