<?php

namespace App\Filament\Tenant\Resources\ChartOfAccounts\Pages;

use App\Filament\Tenant\Resources\ChartOfAccounts\ChartOfAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccount extends EditRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
