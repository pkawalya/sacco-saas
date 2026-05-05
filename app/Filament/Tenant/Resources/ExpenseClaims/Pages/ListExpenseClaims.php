<?php

namespace App\Filament\Tenant\Resources\ExpenseClaims\Pages;

use App\Filament\Tenant\Resources\ExpenseClaims\ExpenseClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseClaims extends ListRecords
{
    protected static string $resource = ExpenseClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
