<?php

namespace App\Filament\Tenant\Resources\NotificationTemplates\Pages;

use App\Filament\Tenant\Resources\NotificationTemplates\NotificationTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
