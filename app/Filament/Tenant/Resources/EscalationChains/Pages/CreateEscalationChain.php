<?php

namespace App\Filament\Tenant\Resources\EscalationChains\Pages;

use App\Filament\Tenant\Resources\EscalationChains\EscalationChainResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEscalationChain extends CreateRecord
{
    protected static string $resource = EscalationChainResource::class;
}
