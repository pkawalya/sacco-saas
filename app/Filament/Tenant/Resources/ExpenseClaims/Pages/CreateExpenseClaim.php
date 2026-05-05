<?php

namespace App\Filament\Tenant\Resources\ExpenseClaims\Pages;

use App\Filament\Tenant\Resources\ExpenseClaims\ExpenseClaimResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseClaim extends CreateRecord
{
    protected static string $resource = ExpenseClaimResource::class;
}
