<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\ChartOfAccount;
use App\Models\Tenant\LoanProduct;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberDocument;
use App\Models\Tenant\MemberGroup;
use App\Models\Tenant\MemberShare;
use App\Models\Tenant\SavingsAccount;
use App\Models\Tenant\SavingsProduct;
use App\Models\Tenant\SavingsTransaction;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FullTenantSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenantUsers();
        $this->seedChartOfAccounts();
        $this->seedSavingsProducts();
        $this->seedLoanProducts();
        $this->seedMemberGroups();
        $this->seedMembers();
        $this->seedSavingsAccounts();
    }

    /**
     * Create tenant-level users with roles.
     */
    protected function seedTenantUsers(): void
    {
        $domain = tenancy()->tenant->domains()->first()->domain ?? 'testing.sacco.test';

        $users = [
            ['name' => 'Branch Manager', 'email' => 'manager@'.$domain, 'role' => 'manager'],
            ['name' => 'Loan Officer', 'email' => 'loans@'.$domain, 'role' => 'staff'],
            ['name' => 'Teller', 'email' => 'teller@'.$domain, 'role' => 'teller'],
            ['name' => 'Accountant', 'email' => 'accountant@'.$domain, 'role' => 'staff'],
            ['name' => 'Admin User', 'email' => 'admin@'.$domain, 'role' => 'admin'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(['email' => $u['email']], [
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password'),
                'role' => $u['role'],
                'is_active' => true,
            ]);
        }

        $this->command->info('  ✓ Seeded '.count($users).' tenant users.');
    }

    /**
     * Standard SACCO chart of accounts.
     */
    protected function seedChartOfAccounts(): void
    {
        $headers = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'balance' => 'debit'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'balance' => 'credit'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'balance' => 'credit'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'balance' => 'credit'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'balance' => 'debit'],
        ];

        foreach ($headers as $h) {
            ChartOfAccount::firstOrCreate(['account_code' => $h['code']], [
                'account_code' => $h['code'],
                'account_name' => $h['name'],
                'account_type' => $h['type'],
                'normal_balance' => $h['balance'],
                'level' => 1,
                'is_header' => true,
                'is_active' => true,
            ]);
        }

        $subAccounts = [
            // Assets
            ['code' => '1100', 'name' => 'Cash on Hand', 'type' => 'asset', 'parent' => '1000', 'sub' => 'current', 'cash' => true],
            ['code' => '1110', 'name' => 'Cash at Bank', 'type' => 'asset', 'parent' => '1000', 'sub' => 'current', 'bank' => true],
            ['code' => '1200', 'name' => 'Loans to Members', 'type' => 'asset', 'parent' => '1000', 'sub' => 'current'],
            ['code' => '1210', 'name' => 'Loan Interest Receivable', 'type' => 'asset', 'parent' => '1000', 'sub' => 'current'],
            ['code' => '1300', 'name' => 'Investments', 'type' => 'asset', 'parent' => '1000', 'sub' => 'non_current'],
            ['code' => '1400', 'name' => 'Fixed Assets', 'type' => 'asset', 'parent' => '1000', 'sub' => 'non_current'],
            ['code' => '1500', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'parent' => '1000', 'sub' => 'current'],
            // Liabilities
            ['code' => '2100', 'name' => 'Member Savings Deposits', 'type' => 'liability', 'parent' => '2000', 'sub' => 'current'],
            ['code' => '2110', 'name' => 'Fixed Deposits', 'type' => 'liability', 'parent' => '2000', 'sub' => 'current'],
            ['code' => '2200', 'name' => 'Interest Payable on Savings', 'type' => 'liability', 'parent' => '2000', 'sub' => 'current'],
            ['code' => '2300', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent' => '2000', 'sub' => 'current'],
            ['code' => '2400', 'name' => 'Borrowings from Banks', 'type' => 'liability', 'parent' => '2000', 'sub' => 'non_current'],
            // Equity
            ['code' => '3100', 'name' => 'Member Share Capital', 'type' => 'equity', 'parent' => '3000'],
            ['code' => '3200', 'name' => 'Statutory Reserves', 'type' => 'equity', 'parent' => '3000'],
            ['code' => '3300', 'name' => 'Retained Earnings', 'type' => 'equity', 'parent' => '3000'],
            // Revenue
            ['code' => '4100', 'name' => 'Interest on Loans', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4200', 'name' => 'Processing Fees', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4300', 'name' => 'Penalty Income', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4400', 'name' => 'Investment Income', 'type' => 'revenue', 'parent' => '4000'],
            ['code' => '4500', 'name' => 'Membership Fees', 'type' => 'revenue', 'parent' => '4000'],
            // Expenses
            ['code' => '5100', 'name' => 'Interest on Savings', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5200', 'name' => 'Salaries & Wages', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5300', 'name' => 'Rent & Utilities', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5400', 'name' => 'Office Supplies', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5500', 'name' => 'Loan Loss Provisions', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5600', 'name' => 'Depreciation', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5700', 'name' => 'Insurance', 'type' => 'expense', 'parent' => '5000'],
            ['code' => '5800', 'name' => 'Professional Fees', 'type' => 'expense', 'parent' => '5000'],
        ];

        foreach ($subAccounts as $s) {
            $parent = ChartOfAccount::where('account_code', $s['parent'])->first();

            ChartOfAccount::firstOrCreate(['account_code' => $s['code']], [
                'account_code' => $s['code'],
                'account_name' => $s['name'],
                'account_type' => $s['type'],
                'account_sub_type' => $s['sub'] ?? null,
                'parent_id' => $parent?->id,
                'level' => 2,
                'is_header' => false,
                'normal_balance' => in_array($s['type'], ['asset', 'expense']) ? 'debit' : 'credit',
                'is_bank_account' => $s['bank'] ?? false,
                'is_cash_account' => $s['cash'] ?? false,
                'is_active' => true,
            ]);
        }

        $this->command->info('  ✓ Seeded '.ChartOfAccount::count().' chart of accounts.');
    }

    /**
     * Standard SACCO savings products.
     */
    protected function seedSavingsProducts(): void
    {
        $products = [
            [
                'product_code' => 'SAV-001',
                'product_name' => 'Regular Savings',
                'product_type' => 'regular',
                'description' => 'Standard savings account for all members. Competitive interest rates with flexible withdrawals.',
                'interest_rate' => 5.0000,
                'interest_computation' => 'daily_average',
                'interest_posting_cycle' => 'quarterly',
                'minimum_balance' => 10000.00,
                'minimum_opening_deposit' => 50000.00,
                'maximum_single_withdrawal' => 5000000.00,
                'free_withdrawals_per_month' => 4,
                'is_active' => true,
            ],
            [
                'product_code' => 'SAV-002',
                'product_name' => 'Fixed Deposit',
                'product_type' => 'fixed_deposit',
                'description' => 'Lock your savings for higher returns. Terms from 3 to 24 months.',
                'interest_rate' => 10.0000,
                'interest_computation' => 'eom_balance',
                'interest_posting_cycle' => 'monthly',
                'minimum_balance' => 500000.00,
                'minimum_opening_deposit' => 500000.00,
                'minimum_tenure_months' => 3,
                'maximum_tenure_months' => 24,
                'early_withdrawal_penalty_rate' => 2.0000,
                'auto_rollover' => true,
                'is_active' => true,
            ],
            [
                'product_code' => 'SAV-003',
                'product_name' => 'Children\'s Savings',
                'product_type' => 'children',
                'description' => 'Build a future for your children. Special rates and no withdrawal until maturity.',
                'interest_rate' => 7.5000,
                'interest_computation' => 'daily_average',
                'interest_posting_cycle' => 'annually',
                'minimum_balance' => 5000.00,
                'minimum_opening_deposit' => 10000.00,
                'is_active' => true,
            ],
            [
                'product_code' => 'SAV-004',
                'product_name' => 'Holiday Savings',
                'product_type' => 'holiday',
                'description' => 'Save for Christmas, Easter, or any holiday. Automatic release on target date.',
                'interest_rate' => 6.0000,
                'interest_computation' => 'min_monthly',
                'interest_posting_cycle' => 'annually',
                'minimum_balance' => 5000.00,
                'minimum_opening_deposit' => 20000.00,
                'is_active' => true,
            ],
        ];

        foreach ($products as $p) {
            SavingsProduct::firstOrCreate(['product_code' => $p['product_code']], $p);
        }

        $this->command->info('  ✓ Seeded '.count($products).' savings products.');
    }

    /**
     * Standard SACCO loan products.
     */
    protected function seedLoanProducts(): void
    {
        $products = [
            [
                'product_code' => 'LN-001',
                'product_name' => 'Development Loan',
                'product_type' => 'term',
                'description' => 'General-purpose term loan for business or personal development.',
                'interest_rate' => 18.0000,
                'interest_method' => 'reducing',
                'interest_period' => 'per_annum',
                'processing_fee_rate' => 2.0000,
                'processing_fee_fixed' => 50000.00,
                'processing_fee_upfront' => true,
                'insurance_rate' => 1.0000,
                'penalty_rate_daily' => 0.0500,
                'grace_period_days' => 30,
                'minimum_tenure_months' => 1,
                'maximum_tenure_months' => 36,
                'minimum_amount' => 100000.00,
                'maximum_amount' => 50000000.00,
                'minimum_guarantors' => 2,
                'maximum_guarantors' => 5,
                'collateral_required' => false,
                'four_eyes_disbursement' => true,
                'is_active' => true,
            ],
            [
                'product_code' => 'LN-002',
                'product_name' => 'Emergency Loan',
                'product_type' => 'emergency',
                'description' => 'Quick-turnaround loan for urgent financial needs. Lower limits, faster processing.',
                'interest_rate' => 24.0000,
                'interest_method' => 'flat',
                'interest_period' => 'per_annum',
                'processing_fee_rate' => 1.0000,
                'processing_fee_fixed' => 20000.00,
                'processing_fee_upfront' => true,
                'insurance_rate' => 0.5000,
                'penalty_rate_daily' => 0.1000,
                'grace_period_days' => 7,
                'minimum_tenure_months' => 1,
                'maximum_tenure_months' => 6,
                'minimum_amount' => 50000.00,
                'maximum_amount' => 5000000.00,
                'minimum_guarantors' => 1,
                'maximum_guarantors' => 2,
                'collateral_required' => false,
                'four_eyes_disbursement' => false,
                'is_active' => true,
            ],
            [
                'product_code' => 'LN-003',
                'product_name' => 'School Fees Loan',
                'product_type' => 'school_fees',
                'description' => 'Education financing for members\' children. Competitive rates, term-aligned repayment.',
                'interest_rate' => 15.0000,
                'interest_method' => 'reducing',
                'interest_period' => 'per_annum',
                'processing_fee_rate' => 1.5000,
                'processing_fee_fixed' => 30000.00,
                'processing_fee_upfront' => true,
                'insurance_rate' => 1.0000,
                'penalty_rate_daily' => 0.0500,
                'grace_period_days' => 14,
                'minimum_tenure_months' => 1,
                'maximum_tenure_months' => 12,
                'minimum_amount' => 200000.00,
                'maximum_amount' => 20000000.00,
                'minimum_guarantors' => 1,
                'maximum_guarantors' => 3,
                'collateral_required' => false,
                'four_eyes_disbursement' => true,
                'is_active' => true,
            ],
            [
                'product_code' => 'LN-004',
                'product_name' => 'Mortgage Loan',
                'product_type' => 'mortgage',
                'description' => 'Long-term housing finance for property purchase or construction.',
                'interest_rate' => 16.0000,
                'interest_method' => 'reducing',
                'interest_period' => 'per_annum',
                'processing_fee_rate' => 3.0000,
                'processing_fee_fixed' => 500000.00,
                'processing_fee_upfront' => true,
                'insurance_rate' => 2.0000,
                'penalty_rate_daily' => 0.0300,
                'grace_period_days' => 60,
                'minimum_tenure_months' => 12,
                'maximum_tenure_months' => 240,
                'minimum_amount' => 10000000.00,
                'maximum_amount' => 500000000.00,
                'minimum_guarantors' => 0,
                'maximum_guarantors' => 0,
                'collateral_required' => true,
                'minimum_coverage_ratio' => 1.5000,
                'four_eyes_disbursement' => true,
                'is_active' => true,
            ],
            [
                'product_code' => 'LN-005',
                'product_name' => 'Group Loan',
                'product_type' => 'group',
                'description' => 'Solidarity group lending for village banking and community groups.',
                'interest_rate' => 20.0000,
                'interest_method' => 'flat',
                'interest_period' => 'per_annum',
                'processing_fee_rate' => 2.0000,
                'processing_fee_fixed' => 10000.00,
                'processing_fee_upfront' => true,
                'insurance_rate' => 1.0000,
                'penalty_rate_daily' => 0.0500,
                'grace_period_days' => 14,
                'minimum_tenure_months' => 1,
                'maximum_tenure_months' => 12,
                'minimum_amount' => 50000.00,
                'maximum_amount' => 10000000.00,
                'minimum_guarantors' => 0,
                'maximum_guarantors' => 0,
                'collateral_required' => false,
                'four_eyes_disbursement' => false,
                'is_active' => true,
            ],
        ];

        foreach ($products as $p) {
            LoanProduct::firstOrCreate(['product_code' => $p['product_code']], $p);
        }

        $this->command->info('  ✓ Seeded '.count($products).' loan products.');
    }

    /**
     * Member groups for lending and savings.
     */
    protected function seedMemberGroups(): void
    {
        $groups = [
            ['name' => 'Kampala Women Traders', 'code' => 'GRP-001', 'description' => 'Market women from Owino and St. Balikuddembe markets.'],
            ['name' => 'Boda Boda Riders Association', 'code' => 'GRP-002', 'description' => 'Motorcycle taxi operators in Kampala Central.'],
            ['name' => 'Ntinda Teachers Group', 'code' => 'GRP-003', 'description' => 'Primary and secondary school teachers in Ntinda.'],
            ['name' => 'Wandegeya Farmers Cooperative', 'code' => 'GRP-004', 'description' => 'Smallholder farmers growing vegetables and maize.'],
            ['name' => 'Youth Entrepreneurs', 'code' => 'GRP-005', 'description' => 'Young people aged 18-35 running small businesses.'],
        ];

        foreach ($groups as $g) {
            MemberGroup::firstOrCreate(['group_code' => $g['code']], [
                'group_name' => $g['name'],
                'group_code' => $g['code'],
                'description' => $g['description'],
                'status' => 'active',
            ]);
        }

        $this->command->info('  ✓ Seeded '.count($groups).' member groups.');
    }

    /**
     * Members with KYC documents and shares.
     */
    protected function seedMembers(): void
    {
        if (Member::count() >= 30) {
            $this->command->info('  ⊘ Members already seeded, skipping.');

            return;
        }

        // 30 active members
        Member::factory()
            ->count(30)
            ->active()
            ->create()
            ->each(function (Member $member) {
                $this->seedDocuments($member, 'verified');
                $this->seedShares($member);
                $member->recalculateKycScore();
            });

        // 10 applicants
        Member::factory()
            ->count(10)
            ->applicant()
            ->create()
            ->each(function (Member $member) {
                $this->seedDocuments($member, 'pending', 2);
            });

        // 5 dormant, 3 suspended, 1 exited, 1 deceased
        Member::factory()->count(5)->dormant()->create();
        Member::factory()->count(3)->suspended()->create();
        Member::factory()->exited()->create();
        Member::factory()->deceased()->create();

        $this->command->info('  ✓ Seeded '.Member::count().' members.');
    }

    /**
     * Savings accounts for some active members.
     */
    protected function seedSavingsAccounts(): void
    {
        if (SavingsAccount::count() > 0) {
            $this->command->info('  ⊘ Savings accounts already seeded, skipping.');

            return;
        }

        $regularProduct = SavingsProduct::where('product_code', 'SAV-001')->first();
        if (! $regularProduct) {
            return;
        }

        $members = Member::where('status', 'active')->take(25)->get();
        $accountNum = 1;

        foreach ($members as $member) {
            $balance = fake()->numberBetween(50000, 5000000);
            $openDate = now()->subDays(fake()->numberBetween(30, 365));
            $account = SavingsAccount::create([
                'account_number' => 'SA-'.str_pad((string) $accountNum++, 5, '0', STR_PAD_LEFT),
                'member_id' => $member->id,
                'product_id' => $regularProduct->id,
                'ledger_balance' => $balance,
                'available_balance' => $balance,
                'held_amount' => 0,
                'status' => 'active',
                'opened_date' => $openDate->toDateString(),
            ]);

            // Add deposit transactions
            $depositCount = fake()->numberBetween(2, 6);
            for ($i = 0; $i < $depositCount; $i++) {
                $txnDate = $openDate->copy()->addDays(fake()->numberBetween(1, 180));
                SavingsTransaction::create([
                    'transaction_ref' => 'TXN-'.fake()->unique()->numerify('########'),
                    'account_id' => $account->id,
                    'member_id' => $member->id,
                    'transaction_type' => 'deposit',
                    'amount' => fake()->numberBetween(50000, 1000000),
                    'running_balance' => $balance,
                    'description' => fake()->randomElement([
                        'Monthly savings deposit',
                        'Cash deposit at branch',
                        'Mobile money deposit',
                        'Salary credit',
                    ]),
                    'reference_number' => 'REF-'.fake()->numerify('######'),
                    'channel' => fake()->randomElement(['branch', 'mobile', 'agent']),
                    'processed_by' => 1,
                    'value_date' => $txnDate->toDateString(),
                    'posted_at' => $txnDate,
                ]);
            }
        }

        $this->command->info('  ✓ Seeded '.SavingsAccount::count().' savings accounts with transactions.');
    }

    /**
     * Seed KYC documents for a member.
     */
    protected function seedDocuments(Member $member, string $status = 'verified', int $count = 4): void
    {
        $types = array_keys(MemberDocument::TYPES);
        $selectedTypes = array_slice($types, 0, $count);

        foreach ($selectedTypes as $type) {
            MemberDocument::create([
                'member_id' => $member->id,
                'document_type' => $type,
                'file_path' => "documents/{$member->member_number}/{$type}.pdf",
                'upload_date' => now()->subDays(fake()->numberBetween(1, 180)),
                'expiry_date' => $type === 'national_id' ? now()->addYears(5) : null,
                'verification_status' => $status,
                'verified_by' => $status === 'verified' ? 1 : null,
                'verified_at' => $status === 'verified' ? now()->subDays(fake()->numberBetween(1, 30)) : null,
            ]);
        }
    }

    /**
     * Seed share record for a member.
     */
    protected function seedShares(Member $member): void
    {
        $sharesHeld = fake()->numberBetween(10, 500);
        $parValue = 10000;

        MemberShare::create([
            'member_id' => $member->id,
            'shares_held' => $sharesHeld,
            'par_value' => $parValue,
            'total_value' => $sharesHeld * $parValue,
            'percentage_of_total' => 0.0000,
        ]);
    }
}
