<?php

namespace App\Filament\Tenant\Resources\LoanApplications\Pages;

use App\Filament\Tenant\Resources\LoanApplications\LoanApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanApplications extends ListRecords
{
    protected static string $resource = LoanApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
