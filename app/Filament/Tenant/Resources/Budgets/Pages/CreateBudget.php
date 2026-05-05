<?php

namespace App\Filament\Tenant\Resources\Budgets\Pages;

use App\Filament\Tenant\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;
}
