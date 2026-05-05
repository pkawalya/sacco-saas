<?php

namespace App\Filament\Tenant\Resources\JournalEntries\Pages;

use App\Filament\Tenant\Resources\JournalEntries\JournalEntryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;
}
