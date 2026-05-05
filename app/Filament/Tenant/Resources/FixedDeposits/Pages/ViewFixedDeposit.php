<?php

namespace App\Filament\Tenant\Resources\FixedDeposits\Pages;

use App\Filament\Tenant\Resources\FixedDeposits\FixedDepositResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedDeposit extends ViewRecord
{
    protected static string $resource = FixedDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
