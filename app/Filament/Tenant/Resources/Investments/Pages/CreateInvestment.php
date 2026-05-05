<?php

namespace App\Filament\Tenant\Resources\Investments\Pages;

use App\Filament\Tenant\Resources\Investments\InvestmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestment extends CreateRecord
{
    protected static string $resource = InvestmentResource::class;
}
