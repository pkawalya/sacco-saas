<?php

namespace App\Filament\Tenant\Resources\Budgets\Pages;

use App\Filament\Tenant\Resources\Budgets\BudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
