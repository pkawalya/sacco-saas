<?php

namespace App\Filament\Tenant\Resources\ChartOfAccounts\Pages;

use App\Filament\Tenant\Resources\ChartOfAccounts\ChartOfAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChartOfAccount extends ViewRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
