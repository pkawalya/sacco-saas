<?php

namespace App\Filament\Tenant\Resources\Loans\Pages;

use App\Filament\Tenant\Resources\Loans\LoanResource;
use App\Filament\Tenant\Widgets\LoanPortfolioWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [LoanPortfolioWidget::class];
    }
}
