<?php

namespace App\Filament\Tenant\Resources\FixedDeposits\Pages;

use App\Filament\Tenant\Resources\FixedDeposits\FixedDepositResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedDeposit extends CreateRecord
{
    protected static string $resource = FixedDepositResource::class;
}
