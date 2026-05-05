<?php

namespace App\Filament\Pages;

use App\Models\Central\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class UISettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'UI Settings';

    protected static ?string $title = 'UI Settings';

    protected static ?string $slug = 'ui-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.ui-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $settings = $user->ui_settings ?? User::DEFAULT_UI_SETTINGS;

        $this->form->fill(array_merge(User::DEFAULT_UI_SETTINGS, $settings));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // ═══════════ APPEARANCE ═══════════════════════
                Section::make('Color Theme')
                    ->description('Choose the primary accent color for the entire admin panel.')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->collapsible()
                    ->schema([
                        Radio::make('color_theme')
                            ->label('Primary Color')
                            ->options([
                                'red' => '🔴 Red',
                                'orange' => '🟠 Orange',
                                'amber' => '🟡 Amber',
                                'yellow' => '💛 Yellow',
                                'lime' => '💚 Lime',
                                'green' => '🟢 Green',
                                'emerald' => '✳️ Emerald',
                                'teal' => '🩵 Teal',
                                'cyan' => '🔵 Cyan',
                                'sky' => '🌤️ Sky',
                                'blue' => '💙 Blue',
                                'indigo' => '🔮 Indigo',
                                'violet' => '💜 Violet',
                                'purple' => '🟣 Purple',
                                'fuchsia' => '🩷 Fuchsia',
                                'pink' => '💖 Pink',
                                'rose' => '🌹 Rose',
                                'slate' => '⬜ Slate',
                                'gray' => '🩶 Gray',
                                'zinc' => '🔘 Zinc',
                            ])
                            ->default('amber')
                            ->columns(4),
                    ]),

                Section::make('Background & Gray Tones')
                    ->description('Choose the neutral gray used for backgrounds, borders, text, and containers.')
                    ->icon(Heroicon::OutlinedSquare3Stack3d)
                    ->collapsible()
                    ->schema([
                        Radio::make('gray_color')
                            ->label('Gray Palette')
                            ->options([
                                'slate' => '🌑 Slate — Cool blue-gray (Default)',
                                'gray' => '⚫ Gray — True neutral gray',
                                'zinc' => '🔩 Zinc — Cool neutral',
                                'neutral' => '⬜ Neutral — Pure gray, no undertone',
                                'stone' => '🪨 Stone — Warm brown-gray',
                            ])
                            ->default('slate')
                            ->descriptions([
                                'slate' => 'A cool blue-tinted gray, the Filament default.',
                                'gray' => 'A balanced neutral gray with slight warmth.',
                                'zinc' => 'A cool metallic gray with no color tint.',
                                'neutral' => 'Pure achromatic gray — no color at all.',
                                'stone' => 'A warm brown-tinted gray for a softer look.',
                            ]),
                    ]),

                Section::make('Dark Mode & Font')
                    ->description('Control theme appearance and typography.')
                    ->icon(Heroicon::OutlinedMoon)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('dark_mode')
                            ->label('Enable Dark Mode Toggle')
                            ->helperText('Show the light/dark mode switcher in the top bar.')
                            ->default(true)
                            ->columnSpanFull(),

                        Select::make('font')
                            ->label('Font Family')
                            ->options([
                                'Inter' => 'Inter (Default)',
                                'Poppins' => 'Poppins',
                                'DM Sans' => 'DM Sans',
                                'Plus Jakarta Sans' => 'Plus Jakarta Sans',
                                'Outfit' => 'Outfit',
                                'Nunito' => 'Nunito',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Lato' => 'Lato',
                                'Montserrat' => 'Montserrat',
                                'Source Sans 3' => 'Source Sans 3',
                                'Figtree' => 'Figtree',
                            ])
                            ->default('Inter')
                            ->native(false)
                            ->searchable(),
                    ]),

                // ═══════════ NAVIGATION ══════════════════════
                Section::make('Navigation Layout')
                    ->description('Choose how the main navigation is displayed.')
                    ->icon(Heroicon::OutlinedBars3)
                    ->collapsible()
                    ->schema([
                        Radio::make('navigation_layout')
                            ->label('Navigation Style')
                            ->options([
                                'sidebar' => '📋 Sidebar — Classic left-side panel',
                                'topbar' => '📌 Top Bar — Horizontal across the top',
                            ])
                            ->default('sidebar')
                            ->live()
                            ->descriptions([
                                'sidebar' => 'Navigation items appear in a vertical panel on the left side.',
                                'topbar' => 'Navigation items appear horizontally along the top.',
                            ]),

                        Select::make('sub_navigation_position')
                            ->label('Sub-Navigation Position')
                            ->helperText('Where sub-navigation tabs appear on resource pages.')
                            ->options([
                                'start' => 'Start — Left sidebar within content area',
                                'end' => 'End — Right sidebar within content area',
                                'top' => 'Top — Horizontal tabs above content',
                            ])
                            ->default('start')
                            ->native(false),
                    ]),

                Section::make('Sidebar Options')
                    ->description('Fine-tune how the sidebar behaves on desktop.')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->collapsible()
                    ->visible(fn ($get): bool => $get('navigation_layout') === 'sidebar')
                    ->schema([
                        Toggle::make('sidebar_collapsible')
                            ->label('Collapsible Sidebar')
                            ->helperText('Allow the sidebar to collapse to icon-only on desktop.')
                            ->default(true),

                        Toggle::make('sidebar_fully_collapsible')
                            ->label('Fully Collapsible Sidebar')
                            ->helperText('Allow the sidebar to be completely hidden on desktop.')
                            ->default(false),
                    ]),

                Section::make('Navigation Helpers')
                    ->description('Toggle breadcrumbs and global search.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('breadcrumbs')
                            ->label('Show Breadcrumbs')
                            ->helperText('Display the breadcrumb trail at the top of each page.')
                            ->default(true),

                        Toggle::make('global_search')
                            ->label('Enable Global Search')
                            ->helperText('Show the search bar in the top navigation.')
                            ->default(true),
                    ]),

                // ═══════════ LAYOUT & DENSITY ════════════════
                Section::make('Content Width')
                    ->description('Control how wide the main content area extends.')
                    ->icon(Heroicon::OutlinedArrowsPointingOut)
                    ->collapsible()
                    ->schema([
                        Select::make('max_content_width')
                            ->label('Maximum Content Width')
                            ->options([
                                'full' => 'Full Width — Stretches the entire width',
                                'screen_2xl' => '2XL — Comfortable width (1536px)',
                                'screen_xl' => 'XL — Narrower layout (1280px)',
                                'screen_lg' => 'Large — Compact layout (1024px)',
                                'seven_xl' => '7XL — Very wide (80rem)',
                            ])
                            ->default('full')
                            ->native(false),
                    ]),

                Section::make('Buttons & Modals')
                    ->description('Control button sizes and modal dialog widths.')
                    ->icon(Heroicon::OutlinedCursorArrowRays)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('button_size')
                            ->label('Default Button Size')
                            ->options([
                                'xs' => 'Extra Small — Compact, minimal padding',
                                'sm' => 'Small — Slightly compact',
                                'md' => 'Medium — Standard (Default)',
                                'lg' => 'Large — More prominent',
                                'xl' => 'Extra Large — Maximum presence',
                            ])
                            ->default('md')
                            ->native(false),

                        Select::make('modal_width')
                            ->label('Default Modal Width')
                            ->helperText('Width of action confirmation modals and form modals.')
                            ->options([
                                'sm' => 'Small — Narrow dialogs',
                                'md' => 'Medium — Compact dialogs',
                                'lg' => 'Large — Standard (Default)',
                                'xl' => 'XL — Wider dialogs',
                                '2xl' => '2XL — Very wide dialogs',
                                '3xl' => '3XL — Extra wide',
                                'screen' => 'Full Screen',
                            ])
                            ->default('lg')
                            ->native(false),
                    ]),

                Section::make('Notifications')
                    ->description('Control where notification toasts appear on screen.')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->collapsible()
                    ->schema([
                        Select::make('notifications_position')
                            ->label('Notification Position')
                            ->options([
                                'top-right' => '↗️ Top Right (Default)',
                                'top-left' => '↖️ Top Left',
                                'top-center' => '⬆️ Top Center',
                                'bottom-right' => '↘️ Bottom Right',
                                'bottom-left' => '↙️ Bottom Left',
                                'bottom-center' => '⬇️ Bottom Center',
                            ])
                            ->default('top-right')
                            ->native(false),
                    ]),

                // ═══════════ TABLES ══════════════════════════
                Section::make('Table Preferences')
                    ->description('Configure how data tables display records.')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('tables_records_per_page')
                            ->label('Records Per Page')
                            ->options([
                                10 => '10 records',
                                15 => '15 records',
                                25 => '25 records (Default)',
                                50 => '50 records',
                                100 => '100 records',
                            ])
                            ->default(25)
                            ->native(false),

                        Toggle::make('tables_dense')
                            ->label('Dense Tables')
                            ->helperText('Reduce row padding to fit more data on screen.')
                            ->default(false),

                        Toggle::make('tables_striped')
                            ->label('Striped Rows')
                            ->helperText('Alternate row background colors for easier reading.')
                            ->default(false),
                    ]),

                // ═══════════ BEHAVIOR ════════════════════════
                Section::make('Behavior')
                    ->description('Control page navigation behavior and safety features.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('spa_mode')
                            ->label('SPA Mode')
                            ->helperText('Use single-page navigation for faster page transitions without full reloads.')
                            ->default(false),

                        Toggle::make('unsaved_changes_alerts')
                            ->label('Unsaved Changes Alerts')
                            ->helperText('Show a warning when navigating away from a page with unsaved form changes.')
                            ->default(true),
                    ]),
                // ═══════════ CURRENCY ════════════════════════
                Section::make('Currency')
                    ->description('Set the default currency used throughout the system for displaying monetary values.')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->collapsible()
                    ->schema([
                        Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'UGX' => '🇺🇬 UGX — Ugandan Shilling',
                                'USD' => '🇺🇸 USD — US Dollar',
                                'EUR' => '🇪🇺 EUR — Euro',
                                'GBP' => '🇬🇧 GBP — British Pound',
                                'KES' => '🇰🇪 KES — Kenyan Shilling',
                                'TZS' => '🇹🇿 TZS — Tanzanian Shilling',
                                'RWF' => '🇷🇼 RWF — Rwandan Franc',
                                'ZAR' => '🇿🇦 ZAR — South African Rand',
                            ])
                            ->default('UGX')
                            ->native(false)
                            ->searchable()
                            ->helperText('This currency is used for displaying balances, contributions, and loan values.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();
        $user->update(['ui_settings' => $data]);

        Notification::make()
            ->title('UI Settings Saved')
            ->body('Your preferences have been applied.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTitle(): string|Htmlable
    {
        return 'UI Settings';
    }
}
