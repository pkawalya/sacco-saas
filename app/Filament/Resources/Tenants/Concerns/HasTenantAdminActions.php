<?php

namespace App\Filament\Resources\Tenants\Concerns;

use App\Jobs\Central\CreateTenantAdminFromCentral;
use App\Models\Tenant\User as TenantUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasTenantAdminActions
{
    /**
     * Returns all admin-related header actions for managing tenant users.
     *
     * @return array<Action>
     */
    protected function getTenantAdminHeaderActions(): array
    {
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $isProvisioned = $this->record->is_provisioned;

        return [
            Action::make('createAdmin')
                ->label('Create Admin User')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible($isSuperAdmin && $isProvisioned)
                ->modalHeading('Create Tenant Admin User')
                ->modalDescription("Create a new admin user for {$this->record->name}. The user will be able to log in to the tenant panel immediately.")
                ->modalWidth('lg')
                ->form($this->getCreateAdminFormSchema())
                ->action(function (array $data): void {
                    CreateTenantAdminFromCentral::dispatch($this->record, $data);

                    Notification::make()
                        ->title('Admin user creation queued')
                        ->body("A new {$data['role']} account for {$data['email']} is being created.")
                        ->success()
                        ->send();
                }),

            Action::make('editAdminUser')
                ->label('Edit Admin')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible($isSuperAdmin && $isProvisioned)
                ->modalHeading("Edit Admin User — {$this->record->name}")
                ->modalDescription("Update an existing user's name, email, role, or account status.")
                ->modalWidth('lg')
                ->form([
                    Select::make('email')
                        ->label('Select User')
                        ->options(fn () => $this->getTenantAdminUsers()->pluck('name', 'email')->map(fn ($name, $email) => "{$name} ({$email})"))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set): void {
                            $user = $this->getTenantAdminUsers()->firstWhere('email', $state);
                            if ($user) {
                                $set('name', $user->name);
                                $set('role', $user->role);
                                $set('is_active', (bool) $user->is_active);
                            }
                        }),
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('role')
                        ->label('Role')
                        ->options(TenantUser::ROLES)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Account Active')
                        ->default(true)
                        ->helperText('Inactive users cannot log in.'),
                ])
                ->action(function (array $data): void {
                    $selectedUser = $this->getTenantAdminUsers()->firstWhere('email', $data['email']);

                    if (! $selectedUser) {
                        Notification::make()
                            ->title('User not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->run(function () use ($data): void {
                        TenantUser::where('email', $data['email'])->update([
                            'name' => $data['name'],
                            'role' => $data['role'],
                            'is_active' => $data['is_active'],
                        ]);
                    });

                    Notification::make()
                        ->title('User updated')
                        ->body("Details for {$data['email']} have been updated.")
                        ->success()
                        ->send();
                }),

            Action::make('manageAdmins')
                ->label('View Admins')
                ->icon('heroicon-o-users')
                ->color('gray')
                ->visible($isSuperAdmin && $isProvisioned)
                ->modalHeading("Admin Users — {$this->record->name}")
                ->modalWidth('4xl')
                ->modalContent(fn () => view('filament.tenants.partials.admin-users-list', [
                    'users' => $this->getTenantAdminUsers(),
                    'tenant' => $this->record,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('resetAdminPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible($isSuperAdmin && $isProvisioned)
                ->modalHeading('Reset Admin Password')
                ->modalDescription("Reset the password for an existing admin user in {$this->record->name}.")
                ->modalWidth('lg')
                ->form([
                    Select::make('email')
                        ->label('Select Admin User')
                        ->options(fn () => $this->getTenantAdminUsers()->pluck('name', 'email')->map(fn ($name, $email) => "{$name} ({$email})"))
                        ->searchable()
                        ->required(),
                    TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->default(fn () => Str::password(12))
                        ->suffixAction(
                            Action::make('generatePassword')
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn ($set) => $set('password', Str::password(12)))
                        ),
                    Toggle::make('send_email')
                        ->label('Send new credentials via email')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $selectedUser = $this->getTenantAdminUsers()->firstWhere('email', $data['email']);

                    if (! $selectedUser) {
                        Notification::make()
                            ->title('User not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    CreateTenantAdminFromCentral::dispatch($this->record, [
                        'name' => $selectedUser->name,
                        'email' => $data['email'],
                        'password' => $data['password'],
                        'role' => $selectedUser->role,
                        'send_email' => $data['send_email'],
                    ]);

                    Notification::make()
                        ->title('Password reset queued')
                        ->body("New credentials for {$data['email']} will be applied shortly.")
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Returns the form schema for creating a new tenant admin user.
     *
     * @return array<Component>
     */
    protected function getCreateAdminFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Full Name')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Jane Nakamya'),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required()
                ->placeholder('e.g. jane@sacco.ug'),
            Select::make('role')
                ->label('Role')
                ->options(TenantUser::ROLES)
                ->default(TenantUser::ROLE_ADMIN)
                ->required(),
            TextInput::make('password')
                ->label('Initial Password')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->default(fn () => Str::password(12))
                ->helperText('User will be prompted to change this on first login.')
                ->suffixAction(
                    Action::make('generatePassword')
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn ($set) => $set('password', Str::password(12)))
                ),
            Toggle::make('send_email')
                ->label('Send login credentials via email')
                ->default(true)
                ->helperText('An email with the login URL and password will be sent to the user.'),
        ];
    }

    /**
     * Retrieves all users from the tenant database.
     *
     * @return Collection<int, TenantUser>
     */
    protected function getTenantAdminUsers(): Collection
    {
        $users = collect();

        $this->record->run(function () use (&$users): void {
            $users = TenantUser::query()
                ->orderByRaw("FIELD(role, 'admin', 'manager', 'staff', 'teller')")
                ->get();
        });

        return $users;
    }
}
