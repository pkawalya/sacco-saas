<?php

namespace App\Filament\Tenant\Resources\FixedDeposits\Pages;

use App\Filament\Tenant\Resources\FixedDeposits\FixedDepositResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFixedDeposits extends ListRecords
{
    protected static string $resource = FixedDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
