<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchasePlan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Purchase Plan';

    protected static ?string $slug = 'purchase-plan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.purchase-plan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Select a Plan')
                    ->schema([
                        Radio::make('plan_id')
                            ->label('Choose your subscription plan')
                            ->options(Plan::where('is_active', true)->get()->pluck('name', 'id'))
                            ->descriptions(Plan::where('is_active', true)->get()->mapWithKeys(fn ($plan) => [
                                $plan->id => $plan->currency.' '.number_format($plan->price, 2).' / '.$plan->billing_cycle,
                            ]))
                            ->required(),
                    ]),

                Section::make('Tenant Details')
                    ->description('Set up your business workspace')
                    ->schema([
                        TextInput::make('tenant_name')
                            ->label('Business Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $set('subdomain', Str::slug($state))),
                        TextInput::make('subdomain')
                            ->label('Subdomain')
                            ->required()
                            ->prefix('https://')
                            ->suffix('.'.config('tenancy.central_domains.0'))
                            ->unique(table: 'tenants', column: 'id')
                            ->regex('/^[a-z0-9\-]+$/')
                            ->helperText('Only lowercase letters, numbers, and hyphens.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(TenantProvisioningService $provisioningService): void
    {
        $data = $this->form->getState();

        $plan = Plan::find($data['plan_id']);

        // 1. Create Tenant (Is not provisioned yet)
        $tenant = Tenant::create([
            'id' => $data['subdomain'],
            'name' => $data['tenant_name'],
            'central_user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'is_provisioned' => false,
        ]);

        // 2. Create Pending Invoice
        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.strtoupper(Str::random(10)),
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => 'pending',
            'description' => "Subscription for {$plan->name} Plan",
        ]);

        Notification::make()
            ->title('Order placed successfully!')
            ->body('Your invoice '.$invoice->invoice_number.' has been generated. Please complete the payment to activate your workspace.')
            ->success()
            ->send();

        $this->redirect('/admin/invoices'); // Redirect to invoices list
    }
}
