<?php

namespace App\Filament\Tenant\Resources\SavingsProducts\Pages;

use App\Filament\Tenant\Resources\SavingsProducts\SavingsProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSavingsProduct extends ViewRecord
{
    protected static string $resource = SavingsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
