<?php

namespace App\Filament\Tenant\Resources\ChartOfAccounts\Pages;

use App\Filament\Tenant\Resources\ChartOfAccounts\ChartOfAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
