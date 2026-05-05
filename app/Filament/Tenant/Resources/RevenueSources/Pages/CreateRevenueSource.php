<?php

namespace App\Filament\Tenant\Resources\RevenueSources\Pages;

use App\Filament\Tenant\Resources\RevenueSources\RevenueSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRevenueSource extends CreateRecord
{
    protected static string $resource = RevenueSourceResource::class;
}
