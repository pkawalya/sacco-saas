<?php

namespace App\Models\Tenant;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Tenant-scoped user for SACCO staff authentication.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property bool $is_active
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_STAFF = 'staff';

    public const ROLE_TELLER = 'teller';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_STAFF => 'Staff',
        self::ROLE_TELLER => 'Teller',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'must_change_password',
        'last_login_ip',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Get a specific UI setting with a fallback to the default.
     */
    public function getUiSetting(string $key, mixed $default = null): mixed
    {
        // Tenant users don't have UI settings, return default
        return $default;
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->role === $role;
        }

        return false;
    }

    /**
     * Check if the user has any of the specified roles.
     */
    public function hasAnyRole($roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }

        return $this->hasRole($roles);
    }
}
