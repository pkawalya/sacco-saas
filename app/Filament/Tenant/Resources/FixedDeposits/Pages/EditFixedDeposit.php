<?php

namespace App\Filament\Tenant\Resources\FixedDeposits\Pages;

use App\Filament\Tenant\Resources\FixedDeposits\FixedDepositResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFixedDeposit extends EditRecord
{
    protected static string $resource = FixedDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
