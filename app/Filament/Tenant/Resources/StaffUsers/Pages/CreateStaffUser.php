<?php

namespace App\Filament\Tenant\Resources\StaffUsers\Pages;

use App\Filament\Tenant\Resources\StaffUsers\StaffUserResource;
use App\Mail\StaffWelcomeMail;
use App\Models\Tenant\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateStaffUser extends CreateRecord
{
    protected static string $resource = StaffUserResource::class;

    /** Plain-text password generated before hashing, so we can email it. */
    private string $plainPassword = '';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a strong random password if none provided
        if (empty($data['password'])) {
            $this->plainPassword = Str::password(12);
            $data['password'] = bcrypt($this->plainPassword);
        } else {
            $this->plainPassword = $data['password'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $tenant = tenant();
        $saccoName = $tenant?->name ?? config('app.name');
        $panelUrl = request()->getScheme().'://'.request()->getHost().'/app';

        Mail::to($user->email)->queue(
            new StaffWelcomeMail(
                staffUser: $user,
                plainPassword: $this->plainPassword,
                saccoName: $saccoName,
                panelUrl: $panelUrl,
            )
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
