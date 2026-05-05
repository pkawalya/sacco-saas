<?php

namespace App\Filament\Tenant\Resources\Investments\Pages;

use App\Filament\Tenant\Resources\Investments\InvestmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestment extends ViewRecord
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
