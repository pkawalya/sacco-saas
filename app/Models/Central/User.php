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
        return $this->hasVerifiedEmail();
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
