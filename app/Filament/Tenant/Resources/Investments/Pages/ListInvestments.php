<?php

namespace App\Filament\Tenant\Resources\Investments\Pages;

use App\Filament\Tenant\Resources\Investments\InvestmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvestments extends ListRecords
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
