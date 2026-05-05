<?php

namespace App\Filament\Tenant\Resources\LoanProducts\Pages;

use App\Filament\Tenant\Resources\LoanProducts\LoanProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanProducts extends ListRecords
{
    protected static string $resource = LoanProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
