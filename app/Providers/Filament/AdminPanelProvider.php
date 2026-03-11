<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\MyTenants;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TenantGrowthChart;
use App\Models\Central\User;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Livewire\Notifications;
use Filament\Pages;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Map of color names to their Filament Color constants.
     *
     * @var array<string, array<int, string>>
     */
    public const COLOR_MAP = [
        'slate' => Color::Slate,
        'gray' => Color::Gray,
        'zinc' => Color::Zinc,
        'red' => Color::Red,
        'orange' => Color::Orange,
        'amber' => Color::Amber,
        'yellow' => Color::Yellow,
        'lime' => Color::Lime,
        'green' => Color::Green,
        'emerald' => Color::Emerald,
        'teal' => Color::Teal,
        'cyan' => Color::Cyan,
        'sky' => Color::Sky,
        'blue' => Color::Blue,
        'indigo' => Color::Indigo,
        'violet' => Color::Violet,
        'purple' => Color::Purple,
        'fuchsia' => Color::Fuchsia,
        'pink' => Color::Pink,
        'rose' => Color::Rose,
    ];

    /**
     * @var array<string, Width>
     */
    public const MODAL_WIDTH_MAP = [
        'sm' => Width::Small,
        'md' => Width::Medium,
        'lg' => Width::Large,
        'xl' => Width::ExtraLarge,
        '2xl' => Width::TwoExtraLarge,
        '3xl' => Width::ThreeExtraLarge,
        'screen' => Width::Screen,
    ];

    /**
     * @var array<string, Size>
     */
    public const SIZE_MAP = [
        'xs' => Size::ExtraSmall,
        'sm' => Size::Small,
        'md' => Size::Medium,
        'lg' => Size::Large,
        'xl' => Size::ExtraLarge,
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->domains([
                config('tenancy.central_domain'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->registration()

            // ─── Appearance ─────────────────────────────
            ->colors(fn (): array => $this->resolveColors())
            ->darkMode((bool) $this->setting('dark_mode', true))
            ->font(fn (): string => $this->setting('font', 'Inter'))

            // ─── Navigation ─────────────────────────────
            ->topNavigation(fn (): bool => $this->setting('navigation_layout') === 'topbar')
            ->sidebarCollapsibleOnDesktop(function (): bool {
                if ($this->setting('navigation_layout') === 'topbar') {
                    return false;
                }

                return (bool) $this->setting('sidebar_collapsible', true);
            })
            ->sidebarFullyCollapsibleOnDesktop(function (): bool {
                if ($this->setting('navigation_layout') === 'topbar') {
                    return false;
                }

                return (bool) $this->setting('sidebar_fully_collapsible', false);
            })
            ->breadcrumbs(fn (): bool => (bool) $this->setting('breadcrumbs', true))
            ->globalSearch((bool) $this->setting('global_search', true))
            ->subNavigationPosition(fn (): SubNavigationPosition => match ($this->setting('sub_navigation_position', 'start')) {
                'end' => SubNavigationPosition::End,
                'top' => SubNavigationPosition::Top,
                default => SubNavigationPosition::Start,
            })

            // ─── Behavior ───────────────────────────────
            ->spa(fn (): bool => (bool) $this->setting('spa_mode', false))
            ->unsavedChangesAlerts(fn (): bool => (bool) $this->setting('unsaved_changes_alerts', true))

            // ─── Layout ─────────────────────────────────
            ->maxContentWidth(Width::Full)

            // ─── Discovery ──────────────────────────────
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                MyTenants::class,
                TenantGrowthChart::class,
            ])

            // ─── Middleware ─────────────────────────────
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }

    /**
     * Apply global component defaults that require configureUsing().
     */
    public function boot(): void
    {
        // Re-register colors after auth middleware using Filament::serving()
        Filament::serving(function (): void {
            $colors = $this->resolveColors();
            FilamentColor::register($colors);
        });

        // Default button size
        $buttonSize = self::SIZE_MAP[$this->setting('button_size', 'md')] ?? Size::Medium;
        Action::configureUsing(fn (Action $action) => $action->defaultSize($buttonSize));

        // Default modal width
        $modalWidth = self::MODAL_WIDTH_MAP[$this->setting('modal_width', 'lg')] ?? Width::Large;
        Action::configureUsing(fn (Action $action) => $action->modalWidth($modalWidth));

        // Table defaults
        $striped = (bool) $this->setting('tables_striped', false);
        $perPage = (int) $this->setting('tables_records_per_page', 25);
        Table::configureUsing(function (Table $table) use ($striped, $perPage): void {
            if ($striped) {
                $table->striped();
            }
            $table->defaultPaginationPageOption($perPage);
        });

        // Notification position
        $position = $this->setting('notifications_position', 'top-right');
        [$vertical, $horizontal] = match ($position) {
            'top-left' => [VerticalAlignment::Start, Alignment::Left],
            'top-center' => [VerticalAlignment::Start, Alignment::Center],
            'top-right' => [VerticalAlignment::Start, Alignment::Right],
            'bottom-left' => [VerticalAlignment::End, Alignment::Left],
            'bottom-center' => [VerticalAlignment::End, Alignment::Center],
            'bottom-right' => [VerticalAlignment::End, Alignment::Right],
            default => [VerticalAlignment::Start, Alignment::Right],
        };
        Notifications::$alignment = $horizontal;
        Notifications::$verticalAlignment = $vertical;
    }

    /**
     * Map of gray color names to their Filament Color constants.
     *
     * @var array<string, array<int, string>>
     */
    public const GRAY_MAP = [
        'slate' => Color::Slate,
        'gray' => Color::Gray,
        'zinc' => Color::Zinc,
        'neutral' => Color::Neutral,
        'stone' => Color::Stone,
    ];

    /**
     * Resolve the primary and gray color palettes from user preferences.
     *
     * @return array<string, array<int, string>>
     */
    protected function resolveColors(): array
    {
        $colorName = $this->setting('color_theme', 'amber');
        $primaryPalette = self::COLOR_MAP[$colorName] ?? Color::Amber;

        $grayName = $this->setting('gray_color', 'slate');
        $grayPalette = self::GRAY_MAP[$grayName] ?? Color::Slate;

        return [
            'primary' => $primaryPalette,
            'gray' => $grayPalette,
        ];
    }

    /**
     * Shorthand to read a UI setting from the authenticated user.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return $default ?? (User::DEFAULT_UI_SETTINGS[$key] ?? null);
        }

        return $user->getUiSetting($key, $default);
    }
}
