<?php

namespace Database\Seeders;

use App\Models\Central\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allModuleKeys = array_keys(config('modules', []));

        // Stage 1 core modules
        $stage1 = ['member_management', 'savings_deposits', 'loan_management', 'general_ledger', 'notifications_engine'];

        // Stage 1 + Stage 2
        $stage2 = array_merge($stage1, [
            'digital_channels',
            'revenue_expense',
            'cost_centres',
            'regulatory_compliance',
            'collections_engine',
        ]);

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 49.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_months' => 1,
                'description' => 'For village SACCOs starting their digital journey. Up to 500 members.',
                'stage' => 1,
                'modules' => $stage1,
                'data' => [
                    'max_members' => 500,
                    'max_branches' => 1,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price' => 149.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_months' => 1,
                'description' => 'For established SACCOs scaling operations. Up to 5,000 members.',
                'stage' => 2,
                'modules' => $stage2,
                'data' => [
                    'max_members' => 5000,
                    'max_branches' => 10,
                    'mobile_money' => true,
                    'credit_scoring' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 399.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_months' => 1,
                'description' => 'For MFBs and large cooperatives. Unlimited members, full compliance.',
                'stage' => 4,
                'modules' => $allModuleKeys,
                'data' => [
                    'max_members' => 999999,
                    'max_branches' => 999,
                    'mobile_money' => true,
                    'credit_scoring' => true,
                    'custom_domain' => true,
                    'priority_support' => true,
                    'sla_uptime' => 99.9,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
