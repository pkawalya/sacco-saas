<?php

namespace App\Filament\Tenant\Resources\EscalationChains\Pages;

use App\Filament\Tenant\Resources\EscalationChains\EscalationChainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEscalationChain extends EditRecord
{
    protected static string $resource = EscalationChainResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
