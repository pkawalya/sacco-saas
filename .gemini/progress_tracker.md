# HSMS Progress Tracker

**Last Updated:** 2026-03-12
**Total FRs:** 138 | **Completed:** 8 | **In Progress:** 4

---

## Phase 1 — Foundation (Months 1–4)

### Sprint 1.1: Module Gating System [✅ DONE]
- [x] Add `modules` JSON column to `plans` table (migration)
- [x] Create `config/modules.php` module registry
- [x] Create `App\Services\ModuleService` (isActive, gate, getActiveModules)
- [x] Update `Plan` model with `modules` cast and helper methods
- [x] Add module toggle checkboxes to Plan create/edit forms
- [x] Create tenant-side `TenantPanelProvider` with module-gated discovery
- [x] Write tests for ModuleService
- [x] Verify module gating works end-to-end

### Sprint 1.2: Member Management [🔄 IN PROGRESS]
**FRs:** FR-MM-001 through FR-MM-023 (12 Stage 1 FRs)

#### Database & Models
- [x] Migration: `create_members_table`
  - Columns: member_number, first_name, last_name, dob, gender, nationality, national_id_type, national_id_number, physical_address, postal_address, primary_phone, secondary_phone, email, occupation, employer, monthly_income_range, nok_name, nok_relationship, nok_contact, photo_path, member_category, referral_source, kyc_score, status (applicant/active/dormant/suspended/deceased/exited), branch_id
- [x] Migration: `create_member_documents_table`
  - Columns: member_id, document_type, file_path, upload_date, expiry_date, verification_status, verified_by, verified_at
- [x] Migration: `create_member_shares_table`
  - Columns: member_id, shares_held, par_value, total_value, percentage_of_total
- [x] Migration: `create_member_groups_table`
  - Columns: group_name, group_code, branch_id, status
- [x] Migration: `create_member_group_members_table` (pivot)
  - Columns: group_id, member_id, role (chairperson/secretary/treasurer/member)
- [x] Migration: `create_member_state_history_table`
  - Columns: member_id, from_state, to_state, reason_code, notes, acted_by, timestamp
- [x] Model: `Member` with relationships, casts, scopes
- [x] Model: `MemberDocument`
- [x] Model: `MemberShare`
- [x] Model: `MemberGroup`
- [x] Model: `MemberStateHistory`
- [ ] Factory: `MemberFactory` with states for each lifecycle status
- [ ] Seeder: `MemberSeeder` with realistic test data

#### Filament Resource
- [x] Resource: `MemberResource`
  - [x] Table: searchable (member_number, name, national_id), filters (status, branch, gender, category, date_range), export
  - [x] Frozen columns: member_number, full_name, status
  - [x] Create form: full registration form (FR-MM-002)
  - [x] Edit form: same as create with lifecycle controls
  - [x] View page: Infolist with sections (Personal, Contact, Employment, KYC, Shares, History)
- [ ] Resource: `MemberDocumentResource` (relation manager on MemberResource)
- [x] Resource: `MemberGroupResource`

#### Business Logic
- [x] FR-MM-003: Unique national ID validation (custom rule)
- [x] FR-MM-004: Member number auto-generation service
- [x] FR-MM-007: KYC completeness score computation
- [x] FR-MM-010: Exit block validation (check all obligations)
- [ ] FR-MM-011: Auto-dormancy command/job (EOD batch)
- [x] FR-MM-012: State change observer → log to history table
- [ ] FR-MM-020–023: Share capital tracking & dividend computation

#### Tests
- [ ] Feature test: Member CRUD
- [ ] Feature test: Unique national ID enforcement
- [ ] Feature test: Member number generation
- [ ] Feature test: KYC score computation
- [ ] Feature test: Lifecycle state transitions
- [ ] Feature test: Exit block validation
- [ ] Unit test: Share capital calculations

### Sprint 1.3: Savings & Deposits [NOT STARTED]
**FRs:** FR-SD-001 through FR-SD-023 (12 Stage 1 FRs)

#### Database & Models
- [ ] Migration: `create_savings_products_table`
- [ ] Migration: `create_savings_accounts_table`
- [ ] Migration: `create_savings_transactions_table`
- [ ] Migration: `create_fixed_deposits_table`
- [ ] Migration: `create_interest_accruals_table`
- [ ] Model: `SavingsProduct` with tier rate support
- [ ] Model: `SavingsAccount`
- [ ] Model: `SavingsTransaction`
- [ ] Model: `FixedDeposit`
- [ ] Model: `InterestAccrual`
- [ ] Factories & Seeders

#### Filament Resource
- [ ] Resource: `SavingsProductResource` (config screen)
- [ ] Resource: `SavingsAccountResource`
  - [ ] Table: frozen (account_number, member_name, product)
  - [ ] Filters: product, status, branch, balance_range
  - [ ] View: Infolist with balance display (held vs available)
- [ ] Resource: `SavingsTransactionResource`
- [ ] Resource: `FixedDepositResource` with maturity tracking

#### Business Logic
- [ ] FR-SD-002: Minimum balance enforcement service
- [ ] FR-SD-003: Tiered interest rate computation
- [ ] FR-SD-012: Multi-channel deposit processing
- [ ] FR-SD-014: Account closure checklist enforcement
- [ ] FR-SD-020: Interest computation engine (daily/monthly/EOM)
- [ ] FR-SD-022: Fixed deposit maturity & rollover handler

#### Tests
- [ ] Feature test: Product CRUD
- [ ] Feature test: Deposit & withdrawal flows
- [ ] Feature test: Minimum balance enforcement
- [ ] Feature test: Interest computation accuracy
- [ ] Feature test: Fixed deposit lifecycle

### Sprint 1.4: Loan Management [NOT STARTED]
**FRs:** FR-LM-001 through FR-LM-036 (16 Stage 1 FRs)

#### Database & Models
- [ ] Migration: `create_loan_products_table`
- [ ] Migration: `create_loans_table`
- [ ] Migration: `create_loan_applications_table`
- [ ] Migration: `create_loan_repayments_table`
- [ ] Migration: `create_loan_guarantors_table`
- [ ] Migration: `create_loan_collateral_table`
- [ ] Migration: `create_amortisation_schedules_table`
- [ ] Migration: `create_loan_approvals_table`
- [ ] Models: LoanProduct, Loan, LoanApplication, LoanRepayment, LoanGuarantor, LoanCollateral, AmortisationSchedule, LoanApproval
- [ ] Factories & Seeders

#### Filament Resource
- [ ] Resource: `LoanProductResource` (config screen)
- [ ] Resource: `LoanResource`
  - [ ] Table: frozen (loan_number, member_name, principal)
  - [ ] Filters: product, status, branch, officer, DPD bucket
  - [ ] View: Infolist with amortisation schedule
- [ ] Resource: `LoanApplicationResource` with workflow stages
- [ ] Relation managers: Guarantors, Collateral, Repayments, Approvals

#### Business Logic
- [ ] FR-LM-002: Amortisation schedule generator
- [ ] FR-LM-010: DSCR computation
- [ ] FR-LM-011: Multi-level approval workflow engine
- [ ] FR-LM-012: Four-eyes disbursement enforcement
- [ ] FR-LM-020: Guarantor eligibility validation
- [ ] FR-LM-021: Guarantor savings lock/release
- [ ] FR-LM-030–031: Repayment allocation engine (penalty → interest → principal)
- [ ] FR-LM-032: PAR aging computation (EOD job)

#### Tests
- [ ] Feature test: Loan product config
- [ ] Feature test: Full origination workflow
- [ ] Feature test: Amortisation schedule accuracy
- [ ] Feature test: DSCR computation
- [ ] Feature test: Guarantor lock/release
- [ ] Feature test: Repayment allocation
- [ ] Feature test: PAR aging computation

### Sprint 1.5: General Ledger [NOT STARTED]
**FRs:** FR-GL-001 through FR-GL-023 (10 FRs)

#### Database & Models
- [ ] Migration: `create_chart_of_accounts_table`
- [ ] Migration: `create_journal_entries_table`
- [ ] Migration: `create_journal_lines_table`
- [ ] Migration: `create_accounting_periods_table`
- [ ] Models: ChartOfAccount, JournalEntry, JournalLine, AccountingPeriod
- [ ] Factories & Seeders (including default COA template)

#### Filament Resource
- [ ] Resource: `ChartOfAccountResource` (tree view)
- [ ] Resource: `JournalEntryResource`
  - [ ] Table: frozen (journal_number, date, description)
  - [ ] View: Infolist with debit/credit lines
- [ ] Page: `TrialBalance`
- [ ] Page: `FinancialStatements`

#### Business Logic
- [ ] FR-GL-001: Double-entry enforcement service
- [ ] FR-GL-002: Journal type handling (system/auto-reversal/manual)
- [ ] FR-GL-003: Period close/reopen with audit
- [ ] FR-GL-004: Real-time trial balance view
- [ ] FR-GL-010: EOD batch processor
- [ ] FR-GL-012: EOM batch processor

#### Tests
- [ ] Feature test: Double-entry enforcement
- [ ] Feature test: Period controls
- [ ] Feature test: Trial balance accuracy
- [ ] Feature test: EOD/EOM batch jobs

### Sprint 1.6: Notifications (Basic) [NOT STARTED]
**FRs:** FR-AN-001, FR-AN-010, FR-AN-040–042 (5 FRs)

#### Database & Models
- [ ] Migration: `create_notification_templates_table`
- [ ] Migration: `create_notification_log_table`
- [ ] Models: NotificationTemplate, NotificationLog
- [ ] Seeders: Default notification templates

#### Filament Resource
- [ ] Resource: `NotificationTemplateResource`
- [ ] Resource: `NotificationLogResource` (read-only audit)
  - [ ] Table: frozen (notification_id, recipient, channel)
  - [ ] Filters: channel, status, event, date_range

#### Business Logic
- [ ] FR-AN-001: Channel failover service
- [ ] FR-AN-010: Data masking in notifications
- [ ] FR-AN-040: Template manager with merge fields
- [ ] FR-AN-041: Immutable audit logging

#### Tests
- [ ] Feature test: Notification dispatch
- [ ] Feature test: Failover logic
- [ ] Feature test: Audit log immutability

---

## Phase 2 — Investment & Collections (Months 5–8)

### Sprint 2.1: Revenue & Expense Engine [NOT STARTED]
- [ ] Database & Models (5 tables)
- [ ] Filament Resources (5 resources)
- [ ] Business Logic (FR-RE-001 through FR-RE-034)
- [ ] Tests

### Sprint 2.2: Cost Centres [NOT STARTED]
- [ ] Database & Models (2 tables)
- [ ] Filament Resources (2 resources)
- [ ] Business Logic (FR-CC-001 through FR-CC-004)
- [ ] Tests

### Sprint 2.3: Collections Engine [NOT STARTED]
- [ ] Database & Models (5 tables)
- [ ] Filament Resources (5 resources)
- [ ] Business Logic (FR-CE-001 through FR-CE-031)
- [ ] Tests

### Sprint 2.4: Regulatory Compliance [NOT STARTED]
- [ ] Database & Models (5 tables)
- [ ] Filament Resources (5 resources)
- [ ] Business Logic (FR-RC-001 through FR-RC-022)
- [ ] Tests

### Sprint 2.5: Notifications (Full) [NOT STARTED]
- [ ] Staff alerts with WebSocket dashboard
- [ ] Management digest (Morning Digest)
- [ ] Escalation chains
- [ ] Notification health reports
- [ ] Tests

---

## Phase 3 — Digital Channels (Months 9–12)
### Sprint 3.1: Branch Operations [NOT STARTED]
### Sprint 3.2: Mobile Banking [NOT STARTED]
### Sprint 3.3: USSD Banking [NOT STARTED]
### Sprint 3.4: Agent Banking [NOT STARTED]
### Sprint 3.5: Offline Operations [NOT STARTED]

---

## Phase 4 — Advanced (Months 13–16)
### Sprint 4.1: IFRS 9 & ECL [NOT STARTED]
### Sprint 4.2: Advanced Reporting [NOT STARTED]
### Sprint 4.3: CRB Integration [NOT STARTED]
### Sprint 4.4: Enhanced KYC [NOT STARTED]
### Sprint 4.5: Group Lending [NOT STARTED]

---

## Phase 5 — MFB Upgrade (Months 15–20)
### Sprint 5.1: Current Accounts [NOT STARTED]
### Sprint 5.2: FX & Cards [NOT STARTED]
### Sprint 5.3: ATM & Interbank [NOT STARTED]
### Sprint 5.4: Basel III Reporting [NOT STARTED]

---

## Change Log

| Date | Change | By |
|---|---|---|
| 2026-03-12 | Initial tracker created from SRS v3.0 | AI Assistant |
| 2026-03-12 | Sprint 1.1 COMPLETE: Module gating system, config, service, tests (5 pass) | AI Assistant |
| 2026-03-12 | Sprint 1.2 IN PROGRESS: All migrations, models, resources, core business logic built. 14 tests pass (90 assertions) | AI Assistant |
