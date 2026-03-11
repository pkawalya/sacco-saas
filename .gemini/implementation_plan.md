# HSMS Implementation Plan — Hybrid SACCO Management System

**Version:** 1.0 | **Date:** 2026-03-12 | **SRS Reference:** Hybrid_SACCO_SRS_v3.0 (138 FRs across 10 Modules)

---

## 1. Architecture Overview

### 1.1 Multi-Tenancy & Module Activation

This system is built on **stancl/tenancy** with **Filament v5**. Each SACCO is a **tenant** with its own database. Modules are activated per-tenant via a **subscription/plan** system already in place.

```
Central App (admin panel)
├── Tenant Management (existing)
├── Plan & Subscription Management (existing)
├── Invoice Management (existing)
└── UI Settings (existing)

Tenant App (per-SACCO database)
├── Module 1: Member Management         ← Stage 1
├── Module 2: Savings & Deposits        ← Stage 1
├── Module 3: Loan Management           ← Stage 1
├── Module 4: Revenue & Expense Engine  ← Stage 2
├── Module 5: Cost Centres              ← Stage 2
├── Module 6: General Ledger            ← Stage 1
├── Module 7: Digital Channels          ← Stage 1
├── Module 8: Regulatory Compliance     ← Stage 2
├── Module 9: Collections Engine        ← Stage 2
└── Module 10: Notifications Engine     ← Stage 1
```

### 1.2 Module Gating Strategy

Each `Plan` will define which modules are active. A middleware/service checks module access:

```php
// config/modules.php — Module registry
return [
    'member_management'      => ['stage' => 1, 'label' => 'Member Management'],
    'savings_deposits'       => ['stage' => 1, 'label' => 'Savings & Deposits'],
    'loan_management'        => ['stage' => 1, 'label' => 'Loan Management'],
    'general_ledger'         => ['stage' => 1, 'label' => 'General Ledger & Accounting'],
    'digital_channels'       => ['stage' => 1, 'label' => 'Digital Channels'],
    'notifications_engine'   => ['stage' => 1, 'label' => 'Notifications Engine'],
    'revenue_expense'        => ['stage' => 2, 'label' => 'Revenue & Expense Engine'],
    'cost_centres'           => ['stage' => 2, 'label' => 'Cost Centres'],
    'regulatory_compliance'  => ['stage' => 2, 'label' => 'Regulatory Compliance'],
    'collections_engine'     => ['stage' => 2, 'label' => 'Collections Engine'],
];
```

Each Filament Resource will check `ModuleService::isActive('module_key')` to conditionally register.

### 1.3 Standard Resource Patterns

**Every table in every module** follows these conventions:

| Feature | Implementation |
|---|---|
| **Search** | `->searchable()` on key columns (name, ID, reference number) |
| **Filters** | `SelectFilter`, `DateRangeFilter`, status/type filters per table |
| **Export** | `ExportAction` in toolbar — CSV/XLSX with current filters applied |
| **Row Click → View** | `->recordUrl()` opens the View page |
| **View Page** | Uses **Infolists** (`TextEntry`, `IconEntry`, `RepeatableEntry`) |
| **Frozen Columns** | First 2–3 identity columns (ID, Name, Reference) use `->fixed()` |
| **Dense/Striped** | Controlled by user's UI Settings |
| **Pagination** | Controlled by user's UI Settings (default 25) |

---

## 2. Functional Requirements Extraction — All 10 Modules

### Module 1: Member Management (17 FRs)

**Stage 1 FRs (12):**

| FR ID | Requirement | Priority |
|---|---|---|
| FR-MM-001 | Three registration pathways: branch, web self-service, CSV bulk import | P1 |
| FR-MM-002 | Full registration form: name, DOB, gender, nationality, ID, address, contacts, NOK, photo | P1 |
| FR-MM-003 | Unique national ID enforcement with duplicate detection | P1 |
| FR-MM-004 | Auto-generated member number: `[BRANCH]-[YEAR]-[SEQUENCE]` | P1 |
| FR-MM-005 | Group member accounts with designated officers (chair, secretary, treasurer) | P2 |
| FR-MM-006 | KYC document storage: ID, photo, utility bill, employer letter with verification status | P1 |
| FR-MM-007 | KYC completeness score with configurable threshold restricting services | P2 |
| FR-MM-008 | Member referral source tracking | P3 |
| FR-MM-010 | Exit blocked if outstanding obligations — itemised block list | P1 |
| FR-MM-011 | Auto-dormancy with 30-day warning notification | P2 |
| FR-MM-012 | Complete lifecycle state history with actor, reason code, notes | P1 |
| FR-MM-020–023 | Share capital tracking, minimum thresholds, transfers, dividend computation | P1 |

**Stage 2+ MFB Extensions (5):**

| FR ID | Requirement | Stage |
|---|---|---|
| FR-MM-101 | Corporate/business entity onboarding | Stage 3 |
| FR-MM-102 | Tiered KYC (1/2/3) with auto-enforced limits | Stage 2 |
| FR-MM-103 | PEP/sanctions screening at onboarding + periodic | Stage 2 |
| FR-MM-104 | NIRA/IPRS API integration for ID verification | Stage 2 |
| FR-MM-105 | Relationship mapping for exposure/AML analysis | Stage 3 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `members` | member_number, full_name, national_id | status, branch, gender, category, date_range |
| `member_documents` | member_id, document_type, verification_status | type, status, expiry |
| `member_shares` | member_id, shares_held, total_value | branch, date_range |
| `member_groups` | group_name, member_count | status, branch |
| `member_state_history` | member_id, from_state, to_state, timestamp | state, actor, date_range |

---

### Module 2: Savings & Deposit Management (17 FRs)

**Stage 1 FRs (12):**

| FR ID | Requirement | Priority |
|---|---|---|
| FR-SD-001 | Configurable savings products: name, rates, min/max, penalties, categories | P1 |
| FR-SD-002 | Minimum balance enforcement on withdrawals | P1 |
| FR-SD-003 | Tiered interest rates per product (balance bands) | P2 |
| FR-SD-010 | Multiple accounts per member of different product types | P1 |
| FR-SD-011 | Joint accounts with mandate rules (AOS/BAS) | P2 |
| FR-SD-012 | Deposits from: cash, cheque, mobile money, EFT, payroll, standing order | P1 |
| FR-SD-013 | Display held balance vs available balance separately | P1 |
| FR-SD-014 | Account closure with system-enforced checklist | P2 |
| FR-SD-015 | Inter-account and third-party transfers with limits | P1 |
| FR-SD-020 | Interest computation: daily average, min monthly, EOM balance | P1 |
| FR-SD-021 | Mid-period interest rate changes handled automatically | P2 |
| FR-SD-022 | Fixed deposits: rollover, maturity reminders, early withdrawal penalties | P1 |

**Stage 4 MFB Extensions (5):** Current accounts, debit cards, FX accounts, deposit insurance, cheque books.

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `savings_products` | product_code, product_name, interest_rate | product_type, status, stage |
| `savings_accounts` | account_number, member_name, product_name | product, status, branch, balance_range |
| `savings_transactions` | transaction_ref, account_number, amount | type (deposit/withdrawal), channel, date_range |
| `fixed_deposits` | fd_number, member_name, principal | maturity_status, bank, date_range |
| `interest_accruals` | account_number, accrual_amount, period | product, posting_status, period |

---

### Module 3: Loan Management (21 FRs)

**Stage 1 FRs (16):**

| FR ID | Requirement | Priority |
|---|---|---|
| FR-LM-001 | Configurable loan products: rates, fees, tenures, guarantor rules | P1 |
| FR-LM-002 | Amortisation schedule generation (printable/emailable) | P1 |
| FR-LM-010 | DSCR computation and threshold flagging | P1 |
| FR-LM-011 | Multi-level approval workflows by amount band | P1 |
| FR-LM-012 | Mandatory four-eyes disbursement control | P1 |
| FR-LM-020 | Guarantor eligibility validation (savings coverage) | P1 |
| FR-LM-021 | Guarantor savings lock with auto-release on repayment | P1 |
| FR-LM-022 | Guarantor substitution with approval workflow | P2 |
| FR-LM-023 | Collateral register: type, value, valuation, insurance | P1 |
| FR-LM-024 | Collateral insurance expiry alerts | P2 |
| FR-LM-030 | Multi-channel repayments with configurable allocation order | P1 |
| FR-LM-031 | Repayment allocation breakdown on receipt | P1 |
| FR-LM-032 | PAR aging daily recomputation with branch/product/officer reports | P1 |
| FR-LM-033 | Loan top-up with full appraisal workflow | P2 |
| FR-LM-034 | Loan rescheduling with dual authorisation | P2 |
| FR-LM-035 | Interest/penalty waiver with committee approval | P2 |
| FR-LM-036 | Loan write-off workflow: committee → board → GL journal | P2 |

**Stage 2+ MFB Extensions (5):** CRB integration, group lending, IFRS 9 ECL, syndicated loans, NPL reports.

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `loan_products` | product_code, product_name, interest_rate | type, status |
| `loans` | loan_number, member_name, principal_amount | product, status, branch, officer, DPD bucket |
| `loan_applications` | application_ref, member_name, amount_requested | status (pending/approved/declined), product, date_range |
| `loan_repayments` | receipt_number, loan_number, amount_paid | channel, date_range |
| `loan_guarantors` | loan_number, guarantor_name, guaranteed_amount | status (active/released), loan_status |
| `loan_collateral` | loan_number, asset_type, estimated_value | type, insurance_status |
| `amortisation_schedules` | loan_number, instalment_number, due_date | loan, status (paid/due/overdue) |
| `loan_approvals` | loan_number, approver, decision | level, decision, date_range |

---

### Module 4: Revenue & Expense Engine (12 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-RE-001–004 | Multi-level COA (5+ hierarchical levels) with versioning | P1 |
| FR-RE-010–014 | Revenue source configuration, WHT automation, accrual reporting | P1 |
| FR-RE-020–024 | Budget management (3-tier), expense claims, variance reporting | P1 |
| FR-RE-030–034 | Investment register, type-specific fields, performance dashboard | P2 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `chart_of_accounts` | account_code, account_name, account_type | type, level, status, stage |
| `revenue_sources` | source_name, revenue_type, gl_account | type, recognition_basis, status |
| `budgets` | gl_account, cost_centre, approved_amount | period, status, cost_centre |
| `expense_claims` | claimant, amount, category | status, cost_centre, date_range |
| `investments` | investment_id, name, type, current_value | type, status, counterparty |

---

### Module 5: Cost Centres (5 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-CC-001 | 4-level cost centre hierarchy (Division > Dept > Branch > Unit) | P1 |
| FR-CC-002 | CRUD with historical data preservation on deactivation | P1 |
| FR-CC-003 | Internal charge-backs with transfer pricing | P2 |
| FR-CC-004 | Cost Centre P&L Report reconcilable to Income Statement | P1 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `cost_centres` | code, name, level, parent | level, status, division |
| `cost_allocations` | cost_centre, gl_account, allocated_amount | period, method |

---

### Module 6: General Ledger & Accounting (10 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-GL-001 | Double-entry enforcement on every transaction | P1 |
| FR-GL-002 | Three journal types: system-generated, auto-reversal, manual (dual auth) | P1 |
| FR-GL-003 | Period-end controls (close/reopen with audit) | P1 |
| FR-GL-004 | Real-time Trial Balance (< 30sec refresh) | P1 |
| FR-GL-010–012 | EOD/EOM batch processing with completion reports | P1 |
| FR-GL-020–023 | IFRS financial statements with comparatives and consolidation | P1 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `journal_entries` | journal_number, date, description | type, period, status, date_range |
| `journal_lines` | journal_number, gl_account, debit, credit | account, cost_centre |
| `accounting_periods` | period_name, start_date, end_date | status (open/closed), year |
| `trial_balance` (view) | account_code, account_name, debit_total, credit_total | period, level |

---

### Module 7: Digital Channels (14 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-CH-001–004 | Teller shift management, transaction limits, cash transfers | P1 |
| FR-CH-010–012 | Mobile banking: balances, statements, repayments, transfers | P2 |
| FR-CH-020–021 | USSD banking with PIN security | P3 |
| FR-CH-030–031 | Agent banking: float, limits, commission | P3 |
| FR-CH-040–042 | Offline operations with sync and conflict resolution | P2 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `teller_shifts` | teller_name, branch, opening_balance | status (open/closed), branch, date_range |
| `teller_transactions` | transaction_ref, teller_name, amount | type, shift, date_range |
| `agents` | agent_code, agent_name, float_balance | status, branch |
| `agent_transactions` | transaction_ref, agent_code, amount | type, date_range |
| `offline_sync_queue` | branch, transaction_count, status | sync_status, branch |

---

### Module 8: Regulatory Compliance (10 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-RC-001–003 | Prudential returns auto-generation, deadline tracking, filing register | P1 |
| FR-RC-010–013 | AML transaction monitoring, alert queue, STR/CTR generation | P1 |
| FR-RC-020–022 | CRB submission, PAYE computation, tax compliance calendar | P1 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `regulatory_returns` | return_name, due_date, status | type, status, period |
| `aml_alerts` | alert_id, rule_triggered, member_name | status, rule, severity, date_range |
| `str_reports` | str_reference, member_name, amount | status, date_range |
| `crb_submissions` | submission_date, record_count, status | period, status |
| `tax_calendar` | tax_type, due_date, filing_status | type, status, period |

---

### Module 9: Collections Engine (17 FRs)

**Stage 1–2 FRs (12):**

| FR ID | Requirement | Priority |
|---|---|---|
| FR-CE-001 | Daily delinquency reclassification in EOD | P1 |
| FR-CE-002 | Daily penalty computation on overdue loans | P1 |
| FR-CE-003 | Collections worklist per officer (sorted by DPD) | P1 |
| FR-CE-004 | Auto-escalation when DPD exceeds tier maximum | P1 |
| FR-CE-010 | Collections activity log per loan (calls, visits, PTPs) | P1 |
| FR-CE-011 | PTP capture with auto-flag on broken promise | P1 |
| FR-CE-012 | PTP performance metrics per collector/branch | P2 |
| FR-CE-013 | Demand letters and legal notices from templates | P2 |
| FR-CE-020–022 | Guarantor notification, invocation, collateral enforcement | P1 |
| FR-CE-030–031 | PAR aging, roll-rate, collector scorecard, collections dashboard | P1 |

**Stage 3+ MFB Extensions (5):** IFRS 9 stage migration, DCA integration, legal case register, write-off workflow.

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `collections_worklist` | loan_number, member_name, dpd, arrears | tier, officer, branch, bucket |
| `collections_activities` | loan_number, activity_type, officer | type, date_range, tier |
| `ptp_records` | loan_number, promised_amount, promised_date | status (kept/broken), officer |
| `demand_letters` | loan_number, letter_type, sent_date | type, status |
| `legal_cases` | case_ref, loan_number, court | status, filing_date |

---

### Module 10: Alerting & Notifications Engine (15 FRs)

| FR ID | Requirement | Priority |
|---|---|---|
| FR-AN-001–002 | Channel failover with retry logic; priority per event | P1 |
| FR-AN-010–012 | Member notifications: data masking, multilingual, preferences | P1 |
| FR-AN-020–022 | Staff alerts: dashboard WebSocket, acknowledgement, auto-escalation | P1 |
| FR-AN-030–032 | Management digest, CEO/CFO alerts, escalation chains | P2 |
| FR-AN-040–045 | Template manager, audit log (7yr), health reports, mandatory rules, bulk send | P1 |

**Database Tables:**

| Table | Key Columns (frozen) | Filters |
|---|---|---|
| `notification_templates` | template_code, event_type, channel | event, channel, status |
| `notification_log` | notification_id, recipient, channel, status | channel, status, event, date_range |
| `notification_preferences` | member_id, event_type, channel | channel, event |
| `staff_alerts` | alert_id, event_type, recipient, status | type, status, severity, acknowledged |
| `escalation_chains` | alert_type, tier, recipient_role | type, tier |

---

## 3. Implementation Phases

### Phase 1 — Foundation (Months 1–4) ⭐ START HERE

**Goal:** Core SACCO operations live for first tenant.

| Sprint | Module | Deliverables | Weeks |
|---|---|---|---|
| 1.1 | **Tenant Module System** | Module registry, plan-to-module mapping, module gate middleware, `ModuleService` | 1 |
| 1.2 | **Member Management** | Members CRUD, registration form, lifecycle states, KYC documents, share tracking | 3 |
| 1.3 | **Savings & Deposits** | Savings products config, accounts, deposits/withdrawals, interest computation | 3 |
| 1.4 | **Loan Management** | Loan products, origination workflow, guarantors, collateral, repayments, PAR | 4 |
| 1.5 | **General Ledger** | COA, journal entries, double-entry enforcement, Trial Balance, EOD batch | 3 |
| 1.6 | **Notifications (Basic)** | SMS/email templates, transactional notifications, audit log | 2 |

### Phase 2 — Investment & Collections (Months 5–8)

| Sprint | Module | Deliverables | Weeks |
|---|---|---|---|
| 2.1 | **Revenue & Expense Engine** | COA enhancement, revenue sources, WHT, budget controls, investment register | 3 |
| 2.2 | **Cost Centres** | Hierarchy, allocation rules, P&L by cost centre | 2 |
| 2.3 | **Collections Engine** | Delinquency buckets, worklists, activity log, PTP, demand letters, dashboard | 3 |
| 2.4 | **Regulatory Compliance** | Prudential returns, AML monitoring, CRB submission, tax compliance | 3 |
| 2.5 | **Notifications (Full)** | Staff alerts, management digest, escalation chains, health reports | 2 |

### Phase 3 — Digital Channels (Months 9–12)

| Sprint | Module | Deliverables | Weeks |
|---|---|---|---|
| 3.1 | **Branch Operations** | Teller shifts, cash management, transaction limits, EOD reports | 3 |
| 3.2 | **Mobile Banking** | Member mobile app (React Native/Flutter), API endpoints | 4 |
| 3.3 | **USSD Banking** | USSD gateway integration, menus, PIN security | 2 |
| 3.4 | **Agent Banking** | Agent management, float, commissions | 2 |
| 3.5 | **Offline Operations** | Offline cache, sync engine, conflict resolution | 3 |

### Phase 4 — Advanced Analytics & MFB Prep (Months 13–16)

| Sprint | Module | Deliverables | Weeks |
|---|---|---|---|
| 4.1 | **IFRS 9 & ECL** | Staging, ECL computation (PD × LGD × EAD), provision posting | 3 |
| 4.2 | **Advanced Reporting** | Financial statements, custom report builder, BI connectors | 3 |
| 4.3 | **CRB Integration** | Real-time credit score inquiry, monthly data submission | 2 |
| 4.4 | **Enhanced KYC** | Tiered KYC, PEP/sanctions screening, NIRA/IPRS API | 2 |
| 4.5 | **Group Lending** | Group loans, joint liability, member performance tracking | 2 |

### Phase 5 — MFB Upgrade (Months 15–20)

Current accounts, FX, card management, ATM integration, interbank settlement, Basel III reporting, deposit insurance.

---

## 4. Standard Filament Resource Template

Every resource follows this pattern:

### 4.1 Table Configuration

```php
// Standard table pattern for ALL resources
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ─── FROZEN IDENTITY COLUMNS ─────────
            TextColumn::make('reference_number')
                ->label('Ref #')
                ->searchable()
                ->sortable()
                ->fixed(),      // ← FROZEN

            TextColumn::make('member.full_name')
                ->label('Member')
                ->searchable()
                ->sortable()
                ->fixed(),      // ← FROZEN

            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state) => match ($state) { ... })
                ->fixed(),      // ← FROZEN

            // ─── SCROLLABLE DATA COLUMNS ─────────
            TextColumn::make('amount')
                ->money('UGX')
                ->sortable(),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('status')
                ->options([...]),
            SelectFilter::make('branch')
                ->relationship('branch', 'name'),
            Filter::make('created_at')
                ->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ]),
        ])
        ->recordUrl(fn (Model $record) => static::getUrl('view', ['record' => $record]))
        ->toolbarActions([
            ExportAction::make()
                ->exporter(ModelExporter::class),
        ]);
}
```

### 4.2 View Page (Infolist)

```php
// Standard view page pattern — ALL views use Infolists
public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            \Filament\Infolists\Components\Section::make('Basic Information')
                ->icon('heroicon-o-identification')
                ->columns(3)
                ->schema([
                    TextEntry::make('reference_number')
                        ->label('Reference'),
                    TextEntry::make('member.full_name')
                        ->label('Member Name'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) { ... }),
                ]),

            \Filament\Infolists\Components\Section::make('Financial Details')
                ->icon('heroicon-o-currency-dollar')
                ->columns(2)
                ->schema([
                    TextEntry::make('amount')
                        ->money('UGX'),
                    TextEntry::make('balance')
                        ->money('UGX'),
                ]),

            RepeatableEntry::make('transactions')
                ->label('Transaction History')
                ->schema([
                    TextEntry::make('date')->dateTime(),
                    TextEntry::make('description'),
                    TextEntry::make('amount')->money('UGX'),
                ]),
        ]);
}
```

### 4.3 Frozen Column Convention

| Module | Frozen Columns |
|---|---|
| Members | `member_number`, `full_name`, `status` |
| Savings Accounts | `account_number`, `member_name`, `product_name` |
| Loans | `loan_number`, `member_name`, `principal_amount` |
| Transactions | `transaction_ref`, `account_number`, `amount` |
| GL Journals | `journal_number`, `date`, `description` |
| Collections | `loan_number`, `member_name`, `dpd` |
| Notifications | `notification_id`, `recipient`, `channel` |

---

## 5. Module Gating Implementation

### 5.1 ModuleService

```php
class ModuleService
{
    public static function isActive(string $moduleKey): bool
    {
        $tenant = tenant();
        if (!$tenant) return false;

        $plan = $tenant->plan;
        if (!$plan) return false;

        $activeModules = $plan->modules ?? [];
        return in_array($moduleKey, $activeModules);
    }

    public static function gate(string $moduleKey): void
    {
        if (!static::isActive($moduleKey)) {
            abort(403, 'This module is not included in your subscription plan.');
        }
    }
}
```

### 5.2 Resource Registration Gate

```php
// In each tenant-side Filament Resource
public static function canAccess(): bool
{
    return ModuleService::isActive('member_management');
}
```

### 5.3 Plan Configuration

The `plans` table will include a `modules` JSON column listing active module keys:

```json
{
  "name": "Starter SACCO",
  "stage": 1,
  "modules": [
    "member_management",
    "savings_deposits",
    "loan_management",
    "general_ledger",
    "notifications_engine"
  ]
}
```

---

## 6. Sprint 1.1 — First Deliverable: Module System

**Prerequisites:** Existing tenant/plan infrastructure.

### Tasks:
1. Add `modules` JSON column to `plans` table
2. Create `config/modules.php` registry
3. Create `ModuleService` with `isActive()` and `gate()`
4. Create tenant-side `TenantPanelProvider` with module-gated resource discovery
5. Create `ModuleManager` Filament page for plan administrators
6. Add module toggle UI to Plan create/edit forms

### Estimated Effort: 1 week

---

## 7. Summary Dashboard

| Metric | Value |
|---|---|
| **Total Functional Requirements** | 138 |
| **Total Modules** | 10 |
| **Total Database Tables (estimated)** | ~45 |
| **Total Filament Resources (estimated)** | ~35 |
| **Phase 1 Duration** | 4 months |
| **Full System Duration** | 16–20 months |
| **Stage 1 FRs (core SACCO)** | 118 |
| **Stage 2+ FRs (MFB extensions)** | 20 |
