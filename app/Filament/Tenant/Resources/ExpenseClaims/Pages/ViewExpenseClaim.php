<?php

namespace App\Filament\Tenant\Resources\ExpenseClaims\Pages;

use App\Filament\Tenant\Resources\ExpenseClaims\ExpenseClaimResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseClaim extends ViewRecord
{
    protected static string $resource = ExpenseClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
