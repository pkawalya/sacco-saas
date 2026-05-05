<?php

namespace App\Filament\Tenant\Resources\RevenueSources\Pages;

use App\Filament\Tenant\Resources\RevenueSources\RevenueSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRevenueSource extends EditRecord
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
