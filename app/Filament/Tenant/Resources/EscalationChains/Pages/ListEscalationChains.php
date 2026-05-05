<?php

namespace App\Filament\Tenant\Resources\EscalationChains\Pages;

use App\Filament\Tenant\Resources\EscalationChains\EscalationChainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEscalationChains extends ListRecords
{
    protected static string $resource = EscalationChainResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
