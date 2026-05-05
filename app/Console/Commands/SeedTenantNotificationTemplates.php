<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\NotificationTemplate;
use Illuminate\Console\Command;
use Stancl\Tenancy\Concerns\HasATenantsOption;

class SeedTenantNotificationTemplates extends Command
{
    use HasATenantsOption;

    protected $signature = 'tenants:seed-templates
                            {--tenants=* : Specific tenant IDs (omit for all)}
                            {--force : Overwrite existing templates}';

    protected $description = 'Seed default email notification templates into all tenant databases.';

    /**
     * Core templates seeded into every tenant.
     *
     * Merge field syntax: {field_name}
     *
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            // ─── Member Lifecycle ────────────────────────────────────
            [
                'template_code' => 'MBR-WELCOME-001',
                'name' => 'Member Welcome Email',
                'event_type' => 'member.onboarded',
                'module' => 'member_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Welcome to {sacco_name}, {member_name}!',
                'body' => "Dear {member_name},\n\nWelcome to {sacco_name}! Your membership application has been received.\n\nMember Number: {member_number}\nStatus: Under Review\n\nOur team will review your documents and notify you once your account is activated. You may visit our branch if you have any questions.\n\nWarm regards,\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'member_number', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_NORMAL,
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'template_code' => 'MBR-APPROVED-001',
                'name' => 'Member Account Approved',
                'event_type' => 'member.approved',
                'module' => 'member_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Your {sacco_name} Membership is Approved!',
                'body' => "Dear {member_name},\n\nCongratulations! Your membership with {sacco_name} has been approved.\n\nMember Number: {member_number}\nApproval Date: {approved_date}\n\nYou can now open savings accounts, apply for loans, and access all our services.\n\nBest regards,\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'member_number', 'approved_date', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_HIGH,
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'template_code' => 'MBR-DORMANT-001',
                'name' => 'Dormancy Warning Notice',
                'event_type' => 'member.dormancy_warning',
                'module' => 'member_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Action Required: Your {sacco_name} Account May Go Dormant',
                'body' => "Dear {member_name},\n\nThis is a reminder that your account ({member_number}) has been inactive for {days_inactive} days.\n\nIf no activity is recorded within {days_remaining} days, your account will be marked as dormant.\n\nTo keep your account active, please visit our branch or make a transaction through any of our channels.\n\nFor assistance, contact us at {sacco_phone}.\n\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'member_number', 'days_inactive', 'days_remaining', 'sacco_phone', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_NORMAL,
                'is_mandatory' => false,
                'is_active' => true,
            ],

            // ─── Savings ─────────────────────────────────────────────
            [
                'template_code' => 'SAV-DEPOSIT-001',
                'name' => 'Savings Deposit Confirmation',
                'event_type' => 'savings.deposit',
                'module' => 'savings_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Deposit Confirmed — {sacco_name}',
                'body' => "Dear {member_name},\n\nYour deposit has been processed successfully.\n\nAccount: {account_number}\nAmount Deposited: UGX {amount}\nNew Balance: UGX {new_balance}\nDate: {transaction_date}\nTransaction Ref: {transaction_ref}\n\nThank you for banking with {sacco_name}.\n\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'account_number', 'amount', 'new_balance', 'transaction_date', 'transaction_ref', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_NORMAL,
                'is_mandatory' => false,
                'is_active' => true,
                'mask_sensitive_data' => true,
            ],
            [
                'template_code' => 'SAV-WITHDRAWAL-001',
                'name' => 'Savings Withdrawal Confirmation',
                'event_type' => 'savings.withdrawal',
                'module' => 'savings_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Withdrawal Processed — {sacco_name}',
                'body' => "Dear {member_name},\n\nYour withdrawal has been processed.\n\nAccount: {account_number}\nAmount Withdrawn: UGX {amount}\nRemaining Balance: UGX {new_balance}\nDate: {transaction_date}\nTransaction Ref: {transaction_ref}\n\nIf you did not authorise this transaction, contact us immediately at {sacco_phone}.\n\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'account_number', 'amount', 'new_balance', 'transaction_date', 'transaction_ref', 'sacco_phone', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_HIGH,
                'is_mandatory' => true,
                'is_active' => true,
                'mask_sensitive_data' => true,
            ],

            // ─── Loans ───────────────────────────────────────────────
            [
                'template_code' => 'LN-APPROVED-001',
                'name' => 'Loan Approval Notification',
                'event_type' => 'loan.approved',
                'module' => 'loan_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Your Loan Has Been Approved — {sacco_name}',
                'body' => "Dear {member_name},\n\nGreat news! Your loan application has been approved.\n\nLoan Reference: {loan_ref}\nApproved Amount: UGX {loan_amount}\nInterest Rate: {interest_rate}% p.a.\nTenure: {tenure_months} months\nMonthly Repayment: UGX {monthly_installment}\nDisbursement Date: {disbursement_date}\n\nPlease visit our branch to complete the disbursement process and sign the loan agreement.\n\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'loan_ref', 'loan_amount', 'interest_rate', 'tenure_months', 'monthly_installment', 'disbursement_date', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_HIGH,
                'is_mandatory' => true,
                'is_active' => true,
            ],
            [
                'template_code' => 'LN-REPAYMENT-001',
                'name' => 'Loan Repayment Confirmation',
                'event_type' => 'loan.repayment',
                'module' => 'loan_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Loan Repayment Received — {sacco_name}',
                'body' => "Dear {member_name},\n\nWe have received your loan repayment.\n\nLoan Reference: {loan_ref}\nAmount Paid: UGX {amount_paid}\nOutstanding Balance: UGX {outstanding_balance}\nNext Repayment Date: {next_due_date}\nTransaction Ref: {transaction_ref}\n\nThank you for staying on track with your repayments.\n\n{sacco_name} Team",
                'merge_fields' => ['member_name', 'loan_ref', 'amount_paid', 'outstanding_balance', 'next_due_date', 'transaction_ref', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_NORMAL,
                'is_mandatory' => false,
                'is_active' => true,
            ],
            [
                'template_code' => 'LN-OVERDUE-001',
                'name' => 'Loan Overdue Reminder',
                'event_type' => 'loan.overdue_reminder',
                'module' => 'loan_management',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Loan Payment Overdue — Immediate Action Required',
                'body' => "Dear {member_name},\n\nYour loan repayment is overdue.\n\nLoan Reference: {loan_ref}\nAmount Due: UGX {amount_due}\nDays Past Due: {days_overdue}\nAccrued Penalty: UGX {penalty_amount}\n\nPlease make payment immediately to avoid further penalties and credit implications.\n\nContact us at {sacco_phone} for assistance.\n\n{sacco_name} Collections Team",
                'merge_fields' => ['member_name', 'loan_ref', 'amount_due', 'days_overdue', 'penalty_amount', 'sacco_phone', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_CRITICAL,
                'is_mandatory' => true,
                'is_active' => true,
            ],

            // ─── Staff ───────────────────────────────────────────────
            [
                'template_code' => 'STF-CREATED-001',
                'name' => 'Staff Account Created',
                'event_type' => 'staff.account_created',
                'module' => 'administration',
                'channel' => NotificationTemplate::CHANNEL_EMAIL,
                'subject' => 'Your Staff Account — {sacco_name}',
                'body' => "Dear {staff_name},\n\nYour staff account has been created on the {sacco_name} management system.\n\nLogin URL: {panel_url}\nEmail: {staff_email}\nTemporary Password: {temp_password}\nRole: {staff_role}\n\nPlease log in and change your password immediately.\n\n{sacco_name} Administration",
                'merge_fields' => ['staff_name', 'staff_email', 'temp_password', 'staff_role', 'panel_url', 'sacco_name'],
                'priority' => NotificationTemplate::PRIORITY_HIGH,
                'is_mandatory' => true,
                'is_active' => true,
            ],
        ];
    }

    public function handle(): int
    {
        $tenants = Tenant::all();
        $force = $this->option('force');

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Seeding templates for <fg=cyan>{$tenant->id}</> ({$tenant->name})...");

            $tenant->run(function () use ($force) {
                $created = 0;
                $skipped = 0;

                foreach ($this->templates() as $tpl) {
                    $existing = NotificationTemplate::where('template_code', $tpl['template_code'])->first();

                    if ($existing && ! $force) {
                        $skipped++;

                        continue;
                    }

                    NotificationTemplate::updateOrCreate(
                        ['template_code' => $tpl['template_code']],
                        array_merge($tpl, ['mask_sensitive_data' => $tpl['mask_sensitive_data'] ?? false])
                    );
                    $created++;
                }

                $this->line("  <fg=green>✓</> Templates: {$created} created/updated, {$skipped} skipped");
            });
        }

        $this->newLine();
        $this->info('Done. Use --force to overwrite existing templates.');

        return self::SUCCESS;
    }
}
