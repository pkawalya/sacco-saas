<?php

namespace App\Filament\Tenant\Resources\JournalEntries\Pages;

use App\Filament\Tenant\Resources\JournalEntries\JournalEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;
}
