<?php

namespace App\Filament\Tenant\Resources\LoanApplications\Pages;

use App\Filament\Tenant\Resources\LoanApplications\LoanApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanApplication extends CreateRecord
{
    protected static string $resource = LoanApplicationResource::class;
}
