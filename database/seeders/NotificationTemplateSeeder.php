<?php

namespace Database\Seeders;

use App\Models\Tenant\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds default notification templates for core SACCO events.
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ─── Member Management ──────────────────────────────
            [
                'template_code' => 'MEMBER_REGISTERED',
                'name' => 'Member Registration Confirmation',
                'event_type' => 'member.registered',
                'module' => 'member_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Welcome to the SACCO, {member_name}! Your member number is {member_number}. Visit your nearest branch for more info.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'member_number', 'label' => 'Member Number', 'sample' => 'HQ-2026-0001'],
                ],
                'priority' => 'normal',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],
            [
                'template_code' => 'MEMBER_APPROVED',
                'name' => 'Member Approval Notification',
                'event_type' => 'member.approved',
                'module' => 'member_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, your SACCO membership has been approved. Member #: {member_number}. You can now access all services.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'member_number', 'label' => 'Member Number', 'sample' => 'HQ-2026-0001'],
                ],
                'priority' => 'normal',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],

            // ─── Savings & Deposits ─────────────────────────────
            [
                'template_code' => 'DEPOSIT_CONFIRMED',
                'name' => 'Deposit Confirmation',
                'event_type' => 'savings.deposit',
                'module' => 'savings_deposits',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, UGX {amount} has been deposited to your account {account_number}. Balance: UGX {balance}. Ref: {reference}.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Amount', 'sample' => '500,000'],
                    ['key' => 'account_number', 'label' => 'Account Number', 'sample' => 'SAV-0001'],
                    ['key' => 'balance', 'label' => 'Balance', 'sample' => '1,500,000'],
                    ['key' => 'reference', 'label' => 'Reference', 'sample' => 'TXN-20260312-001'],
                ],
                'priority' => 'high',
                'is_mandatory' => true,
                'mask_sensitive_data' => true,
            ],
            [
                'template_code' => 'WITHDRAWAL_CONFIRMED',
                'name' => 'Withdrawal Confirmation',
                'event_type' => 'savings.withdrawal',
                'module' => 'savings_deposits',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, UGX {amount} has been withdrawn from account {account_number}. Balance: UGX {balance}. Ref: {reference}.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Amount', 'sample' => '200,000'],
                    ['key' => 'account_number', 'label' => 'Account Number', 'sample' => 'SAV-0001'],
                    ['key' => 'balance', 'label' => 'Balance', 'sample' => '1,300,000'],
                    ['key' => 'reference', 'label' => 'Reference', 'sample' => 'TXN-20260312-002'],
                ],
                'priority' => 'high',
                'is_mandatory' => true,
                'mask_sensitive_data' => true,
            ],

            // ─── Loan Management ────────────────────────────────
            [
                'template_code' => 'LOAN_APPLICATION_RECEIVED',
                'name' => 'Loan Application Received',
                'event_type' => 'loan.application_received',
                'module' => 'loan_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, your loan application for UGX {amount} has been received. Application Ref: {reference}. We will update you on the status.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Amount Requested', 'sample' => '5,000,000'],
                    ['key' => 'reference', 'label' => 'Application Reference', 'sample' => 'LA-2026-001'],
                ],
                'priority' => 'normal',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],
            [
                'template_code' => 'LOAN_APPROVED',
                'name' => 'Loan Approval Notification',
                'event_type' => 'loan.approved',
                'module' => 'loan_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, your loan of UGX {amount} has been APPROVED. Loan #: {loan_number}. Disbursement will follow shortly.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Approved Amount', 'sample' => '5,000,000'],
                    ['key' => 'loan_number', 'label' => 'Loan Number', 'sample' => 'LN-2026-001'],
                ],
                'priority' => 'high',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],
            [
                'template_code' => 'LOAN_DISBURSED',
                'name' => 'Loan Disbursement Notification',
                'event_type' => 'loan.disbursed',
                'module' => 'loan_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, UGX {amount} has been disbursed to your account. Loan #: {loan_number}. First instalment due: {due_date}.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Disbursed Amount', 'sample' => '5,000,000'],
                    ['key' => 'loan_number', 'label' => 'Loan Number', 'sample' => 'LN-2026-001'],
                    ['key' => 'due_date', 'label' => 'First Due Date', 'sample' => '2026-04-12'],
                ],
                'priority' => 'high',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],
            [
                'template_code' => 'LOAN_REPAYMENT_RECEIVED',
                'name' => 'Loan Repayment Receipt',
                'event_type' => 'loan.repayment_received',
                'module' => 'loan_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, UGX {amount} payment received for Loan {loan_number}. Outstanding: UGX {outstanding}. Ref: {reference}.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'amount', 'label' => 'Amount Paid', 'sample' => '500,000'],
                    ['key' => 'loan_number', 'label' => 'Loan Number', 'sample' => 'LN-2026-001'],
                    ['key' => 'outstanding', 'label' => 'Outstanding Balance', 'sample' => '4,500,000'],
                    ['key' => 'reference', 'label' => 'Receipt Reference', 'sample' => 'RPT-20260312-001'],
                ],
                'priority' => 'normal',
                'is_mandatory' => true,
                'mask_sensitive_data' => true,
            ],
            [
                'template_code' => 'LOAN_OVERDUE_REMINDER',
                'name' => 'Loan Overdue Reminder',
                'event_type' => 'loan.overdue',
                'module' => 'loan_management',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Dear {member_name}, your Loan {loan_number} has an overdue instalment of UGX {arrears}. Days past due: {dpd}. Please remit urgently.',
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'loan_number', 'label' => 'Loan Number', 'sample' => 'LN-2026-001'],
                    ['key' => 'arrears', 'label' => 'Arrears Amount', 'sample' => '500,000'],
                    ['key' => 'dpd', 'label' => 'Days Past Due', 'sample' => '15'],
                ],
                'priority' => 'critical',
                'is_mandatory' => true,
                'mask_sensitive_data' => false,
            ],

            // ─── Email Templates ────────────────────────────────
            [
                'template_code' => 'MEMBER_REGISTERED_EMAIL',
                'name' => 'Member Registration Email',
                'event_type' => 'member.registered',
                'module' => 'member_management',
                'channel' => 'email',
                'subject' => 'Welcome to the SACCO — Member #{member_number}',
                'body' => "Dear {member_name},\n\nWelcome to the SACCO! Your membership registration is complete.\n\nMember Number: {member_number}\nDate Joined: {join_date}\n\nPlease visit your nearest branch with your original ID documents to complete KYC verification.\n\nThank you.",
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'member_number', 'label' => 'Member Number', 'sample' => 'HQ-2026-0001'],
                    ['key' => 'join_date', 'label' => 'Join Date', 'sample' => '2026-03-12'],
                ],
                'priority' => 'normal',
                'is_mandatory' => false,
                'mask_sensitive_data' => false,
            ],
            [
                'template_code' => 'LOAN_APPROVED_EMAIL',
                'name' => 'Loan Approval Email',
                'event_type' => 'loan.approved',
                'module' => 'loan_management',
                'channel' => 'email',
                'subject' => 'Loan Approved — {loan_number}',
                'body' => "Dear {member_name},\n\nYour loan application has been approved.\n\nLoan Number: {loan_number}\nApproved Amount: UGX {amount}\nInterest Rate: {interest_rate}%\nTenure: {tenure} months\n\nDisbursement will be processed shortly.\n\nThank you.",
                'merge_fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'sample' => 'John Doe'],
                    ['key' => 'loan_number', 'label' => 'Loan Number', 'sample' => 'LN-2026-001'],
                    ['key' => 'amount', 'label' => 'Approved Amount', 'sample' => '5,000,000'],
                    ['key' => 'interest_rate', 'label' => 'Interest Rate', 'sample' => '18'],
                    ['key' => 'tenure', 'label' => 'Tenure (Months)', 'sample' => '12'],
                ],
                'priority' => 'normal',
                'is_mandatory' => false,
                'mask_sensitive_data' => false,
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(
                ['template_code' => $data['template_code']],
                $data,
            );
        }
    }
}
