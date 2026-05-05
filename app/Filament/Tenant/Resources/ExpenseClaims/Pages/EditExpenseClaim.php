<?php

namespace App\Filament\Tenant\Resources\ExpenseClaims\Pages;

use App\Filament\Tenant\Resources\ExpenseClaims\ExpenseClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseClaim extends EditRecord
{
    protected static string $resource = ExpenseClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
