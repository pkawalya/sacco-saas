<?php

namespace Database\Seeders;

use App\Models\Central\Invoice;
use App\Models\Central\Order;
use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Models\Central\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Exceptions\TenantDatabaseAlreadyExistsException;

class CentralDataSeeder extends Seeder
{
    /**
     * Seed rich central data: users, tenants, subscriptions, invoices, orders.
     */
    public function run(): void
    {
        $this->seedExtraUsers();
        $this->seedTenantsAndSubscriptions();
        $this->seedOrders();
    }

    protected function seedExtraUsers(): void
    {
        $users = [
            ['name' => 'Jane Achieng', 'email' => 'jane@sacco.test'],
            ['name' => 'Moses Kasule', 'email' => 'moses@sacco.test'],
            ['name' => 'Grace Nambi', 'email' => 'grace@sacco.test'],
            ['name' => 'David Opio', 'email' => 'david@sacco.test'],
            ['name' => 'Sarah Nakato', 'email' => 'sarah@sacco.test'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(['email' => $u['email']], [
                'name' => $u['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_approved' => true,
                'approved_at' => now(),
            ]);

            $user->assignRole('user');
        }

        $this->command->info('  ✓ Seeded '.count($users).' extra central users.');
    }

    protected function seedTenantsAndSubscriptions(): void
    {
        $plans = Plan::all()->keyBy('slug');
        $starterPlan = $plans->get('starter');
        $growthPlan = $plans->get('growth');
        $enterprisePlan = $plans->get('enterprise');

        if (! $starterPlan || ! $growthPlan) {
            $this->command->warn('  ⚠ Plans not found — run PlanSeeder first.');

            return;
        }

        $tenants = [
            [
                'id' => 'kampala-teachers',
                'name' => 'Kampala Teachers SACCO',
                'domain' => 'kampala-teachers.localhost',
                'plan' => $growthPlan,
                'user' => 'jane@sacco.test',
                'member_format' => 'KTC/{year}{sequence:6}',
            ],
            [
                'id' => 'gulu-farmers',
                'name' => 'Gulu Farmers Cooperative',
                'domain' => 'gulu-farmers.localhost',
                'plan' => $starterPlan,
                'user' => 'moses@sacco.test',
            ],
            [
                'id' => 'mbarara-women',
                'name' => 'Mbarara Women Savings Group',
                'domain' => 'mbarara-women.localhost',
                'plan' => $starterPlan,
                'user' => 'grace@sacco.test',
            ],
            [
                'id' => 'jinja-traders',
                'name' => 'Jinja Traders Credit Union',
                'domain' => 'jinja-traders.localhost',
                'plan' => $growthPlan,
                'user' => 'david@sacco.test',
            ],
            [
                'id' => 'entebbe-microfinance',
                'name' => 'Entebbe Microfinance Bank',
                'domain' => 'entebbe-mfb.localhost',
                'plan' => $enterprisePlan,
                'user' => 'sarah@sacco.test',
            ],
        ];

        foreach ($tenants as $t) {
            $owner = User::where('email', $t['user'])->first();

            $attributes = [
                'name' => $t['name'],
                'central_user_id' => $owner?->id,
                'plan_id' => $t['plan']->id,
                'member_number_format' => $t['member_format'] ?? 'MEM-{year}{sequence:6}',
                'is_provisioned' => true,
                'provisioned_at' => now()->subDays(fake()->numberBetween(7, 90)),
            ];

            try {
                $tenant = Tenant::updateOrCreate(['id' => $t['id']], $attributes);
            } catch (TenantDatabaseAlreadyExistsException) {
                $tenant = Tenant::updateOrCreate(['id' => $t['id']], $attributes);
            }

            $tenant->domains()->firstOrCreate(['domain' => $t['domain']]);

            // Subscription
            $startDate = now()->subDays(fake()->numberBetween(7, 60));
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $t['plan']->id,
                'status' => 'active',
                'starts_at' => $startDate,
                'ends_at' => $startDate->copy()->addMonths($t['plan']->duration_months),
            ]);

            // Invoice
            Invoice::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $t['plan']->id,
                'invoice_number' => 'INV-'.strtoupper($t['id']).'-001',
                'amount' => $t['plan']->price,
                'currency' => $t['plan']->currency,
                'status' => 'paid',
                'description' => 'Subscription payment — '.$t['plan']->name.' plan',
                'paid_at' => $startDate->copy()->addDays(fake()->numberBetween(0, 7)),
            ]);
        }

        $this->command->info('  ✓ Seeded '.count($tenants).' tenants with subscriptions & invoices.');
    }

    protected function seedOrders(): void
    {
        if (Order::count() > 0) {
            $this->command->info('  ⊘ Orders already seeded, skipping.');

            return;
        }

        $orders = [
            [
                'organization_name' => 'Lira Town Council Staff SACCO',
                'contact_person' => 'Peter Otim',
                'email' => 'potim@lirasacco.co.ug',
                'phone' => '+256 772 345 678',
                'plan_tier' => 'starter',
                'billing_cycle' => 'monthly',
                'member_count' => 120,
                'message' => 'We currently use Excel for everything. Need help migrating data.',
                'status' => 'pending',
            ],
            [
                'organization_name' => 'Fort Portal Coffee Growers SACCO',
                'contact_person' => 'Agnes Kyomukama',
                'email' => 'akyomukama@fpcsacco.org',
                'phone' => '+256 701 234 567',
                'plan_tier' => 'growth',
                'billing_cycle' => 'annual',
                'member_count' => 2300,
                'message' => 'Interested in mobile money integration and USSD banking.',
                'status' => 'contacted',
            ],
            [
                'organization_name' => 'Masaka Healthcare Workers SACCO',
                'contact_person' => 'Dr. Robert Ssemakula',
                'email' => 'rssemakula@mhws.co.ug',
                'phone' => '+256 782 678 901',
                'plan_tier' => 'growth',
                'billing_cycle' => 'monthly',
                'member_count' => 800,
                'message' => 'Need loan product for medical equipment financing.',
                'status' => 'pending',
            ],
            [
                'organization_name' => 'Soroti Eastern MFB',
                'contact_person' => 'Florence Akol',
                'email' => 'fakol@sorotimfb.org',
                'phone' => '+256 756 789 012',
                'plan_tier' => 'enterprise',
                'billing_cycle' => 'annual',
                'member_count' => 15000,
                'message' => 'Require full BOU compliance suite including Basel III and CRB reporting.',
                'status' => 'pending',
            ],
        ];

        foreach ($orders as $i => $o) {
            $o['order_number'] = 'ORD-'.date('ym').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            Order::create($o);
        }

        $this->command->info('  ✓ Seeded '.count($orders).' demo orders.');
    }
}
