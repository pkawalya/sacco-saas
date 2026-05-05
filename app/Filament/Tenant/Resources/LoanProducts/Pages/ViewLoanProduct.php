<?php

namespace App\Filament\Tenant\Resources\LoanProducts\Pages;

use App\Filament\Tenant\Resources\LoanProducts\LoanProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanProduct extends ViewRecord
{
    protected static string $resource = LoanProductResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
