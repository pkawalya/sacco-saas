<?php

namespace App\Models\Central;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\Central\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use CentralConnection, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ui_settings',
        'is_approved',
        'approved_at',
        'approved_by',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'must_change_password',
        'last_login_ip',
        'last_login_at',
    ];

    /** @var array<string, mixed> */
    public const DEFAULT_UI_SETTINGS = [
        'color_theme' => 'amber',
        'navigation_layout' => 'sidebar',
        'sidebar_collapsible' => true,
        'sidebar_fully_collapsible' => false,
        'max_content_width' => 'full',
        'font' => 'Inter',
        'dark_mode' => true,
        'breadcrumbs' => true,
        'global_search' => true,
        'tables_records_per_page' => 25,
        'tables_dense' => false,
        'tables_striped' => false,
        'spa_mode' => false,
        'unsaved_changes_alerts' => true,
        'sub_navigation_position' => 'start',
        'button_size' => 'md',
        'modal_width' => 'lg',
        'notifications_position' => 'top-right',
        'gray_color' => 'slate',
        'currency' => 'UGX',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Super admins always have access to all panels
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Check account lockout
        if ($this->isLocked()) {
            return false;
        }

        // Approved and verified users can access any panel
        return $this->is_approved && $this->hasVerifiedEmail();
    }

    /**
     * Check if the account is locked.
     */
    public function isLocked(): bool
    {
        if (! $this->locked_until) {
            return false;
        }

        if ($this->locked_until->isPast()) {
            // Auto-unlock expired lockouts
            $this->forceFill(['locked_until' => null, 'failed_login_attempts' => 0])->save();

            return false;
        }

        return true;
    }

    /**
     * Check if the user account has been approved.
     */
    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }

    /**
     * Approve the user account.
     */
    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);
    }

    /**
     * Revoke approval from the user account.
     */
    public function revokeApproval(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * The roles that should be assigned by default.
     */
    protected static function booted(): void
    {
        // Role assignments are now handled explicitly in seeders or controllers.
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ui_settings' => 'array',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get a specific UI setting with a fallback to the default.
     */
    public function getUiSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->ui_settings ?? [];

        return $settings[$key] ?? ($default ?? (self::DEFAULT_UI_SETTINGS[$key] ?? null));
    }

    /**
     * Determine if the user prefers top navigation.
     */
    public function prefersTopNavigation(): bool
    {
        return $this->getUiSetting('navigation_layout') === 'topbar';
    }
}
