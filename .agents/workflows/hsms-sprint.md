---
description: How to start any HSMS module sprint
---

# HSMS Sprint Workflow

// turbo-all

Follow these steps when starting work on any module sprint.

## 1. Check Current Progress

Read the progress tracker to understand where we are:

```
View file: .gemini/progress_tracker.md
```

## 2. Read the Implementation Plan

Review the module's section in the implementation plan for context:

```
View file: .gemini/implementation_plan.md
```

## 3. Review SRS Requirements

Check the original SRS text for the specific functional requirements:

```
cat /tmp/srs_full.txt | grep -A5 "FR-XX-"
```

(Replace XX with the module prefix: MM, SD, LM, RE, CC, GL, CH, RC, CE, AN)

## 4. Check Existing Code Patterns

Before creating anything, check sibling resources for code patterns:

```bash
ls app/Filament/Resources/
ls app/Models/Tenant/
ls database/migrations/tenant/
```

## 5. Build in This Order

For each module, always build in this sequence:

### Step A: Migrations
```bash
php artisan make:migration create_[table]_table --path=database/migrations/tenant
```

### Step B: Models
```bash
php artisan make:model Tenant/[ModelName]
```
- Add relationships, casts, scopes
- Add factory

### Step C: Filament Resource
```bash
php artisan make:filament-resource [Name] --view --generate
```
- Apply standard patterns: frozen columns, filters, search, export
- View page must use Infolists
- Always check module gate: `ModuleService::isActive('module_key')`

### Step D: Business Logic
- Create service classes in `app/Services/`
- Create jobs in `app/Jobs/Tenant/`
- Create observers if needed

### Step E: Tests
```bash
php artisan make:test --pest [TestName]
```

## 6. Update the Tracker

After completing any task, update the progress tracker:

```
Edit file: .gemini/progress_tracker.md
```

Mark completed items with `[x]` and update sprint status.

## 7. Run Pint & Tests

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## 8. Standard Table Conventions

Every resource table MUST have:

1. **Frozen columns** — First 2-3 identity columns use `->fixed()`
2. **Searchable** — Key text columns (name, reference, ID)
3. **Filters** — Status, type, branch, date range at minimum
4. **Export** — `ExportAction` or `ExportBulkAction` in toolbar
5. **Row click** — `->recordUrl()` opens the View page
6. **Sort** — Default sort by `created_at desc`

## 9. Standard View (Infolist) Conventions

Every view page MUST use:

1. **Sections** with icons and column layouts
2. **TextEntry** for all data display
3. **Badge** entries for status fields
4. **RepeatableEntry** for related records (transactions, history)
5. **ImageEntry** for photos/documents
6. **Actions** for workflow transitions (approve, reject, close)
