<?php

namespace App\Filament\Tenant\Resources\RevenueSources\Pages;

use App\Filament\Tenant\Resources\RevenueSources\RevenueSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRevenueSources extends ListRecords
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
