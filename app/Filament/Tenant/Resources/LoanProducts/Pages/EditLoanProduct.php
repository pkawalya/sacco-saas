<?php

namespace App\Filament\Tenant\Resources\LoanProducts\Pages;

use App\Filament\Tenant\Resources\LoanProducts\LoanProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanProduct extends EditRecord
{
    protected static string $resource = LoanProductResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
