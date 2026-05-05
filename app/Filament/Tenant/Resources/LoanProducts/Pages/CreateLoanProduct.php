<?php

namespace App\Filament\Tenant\Resources\LoanProducts\Pages;

use App\Filament\Tenant\Resources\LoanProducts\LoanProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanProduct extends CreateRecord
{
    protected static string $resource = LoanProductResource::class;
}
