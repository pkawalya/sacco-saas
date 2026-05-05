# Laravel Tenancy Starter Kit

A robust, production-ready starter kit for building multi-tenant applications with [Laravel](https://laravel.com) and [stancl/tenancy](https://tenancyforlaravel.com). This boilerplate comes pre-configured with a powerful Central Admin Panel built on [Filament](https://filamentphp.com) to manage tenants, subscriptions, and billing.

**Built by and actively used at [quikpl.com](https://quikpl.com) to power our own SaaS platform.**

## Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Seeding & Demo Accounts](#seeding--demo-accounts)
- [Usage](#usage)
- [Automating Tenant Provisioning with Payments](#automating-tenant-provisioning-with-payments-stripe-example)
- [Testing](#testing)
- [Useful Documentation](#useful-documentation)
- [Architecture Overview](#architecture-overview)
- [Contributing](#contributing)
- [Custom Development Services](#custom-development-services)
- [License](#license)

## Features

### Multi-Tenancy (stancl/tenancy v3)
- **Database per Tenant:** Complete data isolation with automatic database creation and migration.
- **Domain Identification:** Tenants are identified by domains/subdomains.
- **Tenant Lifecycle Management:** Automated jobs for creating, migrating, and deleting tenant databases.
- **Tenant-Aware Services:** Filesystem, Cache, Queue, and Redis are configured to be tenant-aware.

### Central Administration (Filament v5)
- **Tenant Management:** Create, update, and delete tenants directly from the admin panel.
- **Subscription Billing:**
    - **Plans Management:** Create and manage flexible subscription plans.
    - **Subscription Tracking:** Monitor tenant subscriptions and statuses.
    - **Invoicing:** Generate and manage invoices for tenants.
- **Role-Based Access Control:** integrated with Filament Shield (v3) for granular permissions.

### Modern Stack
- **Framework:** Laravel 12
- **Admin Panel:** Filament v5
- **Frontend:** Livewire v4 + Tailwind CSS v4
- **Testing:** Pest v4
- **Tooling:** Laravel Pint (Code Style), Laravel Sail (Docker)

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL 8.0+ (Recommended) or SQLite
- Redis

**Recommendation:** For local development, especially with subdomains, we highly recommend using [Laravel Valet](https://laravel.com/docs/valet) (macOS) or [Laravel Herd](https://herd.laravel.com/). These tools make handling `*.test` domains seamless.

## Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/danihidayatx/tenancy-starterkit.git
    cd tenancy-starterkit
    ```

2.  **Install PHP dependencies**
    ```bash
    composer install
    ```

3.  **Install Node dependencies**
    ```bash
    npm install
    ```

4.  **Environment Configuration**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Update your `.env` file with your database credentials. Ensure you have a central database created.
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=tenancy_central
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5.  **Run Migrations & Seeds**
    This will migrate the central database and seed default roles/permissions.
    ```bash
    php artisan migrate --seed
    ```

6.  **Build Assets**
    ```bash
    npm run build
    ```

7.  **Generate Shield Permissions & Assign Super Admin**
    First, generate all permissions for Filament Shield. When prompted, select `permissions` for what you'd like to manage.
    ```bash
    php artisan shield:generate --all
    ```
    Next, assign the `super_admin` role to a user. When prompted, choose the `admin` panel and then select the `UserID` of the super admin (usually `1`).
    ```bash
    php artisan shield:super-admin
    ```

## Seeding & Demo Accounts

The application ships with comprehensive seeders that populate both central and tenant databases with realistic SACCO data.

### Running All Seeders

```bash
# 1. Seed central database (roles, plans, users, tenants)
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PlanSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CentralDataSeeder

# 2. Seed staff users into all tenant databases (admin per tenant)
php artisan tenants:seed-users

# 3. Seed demo members + staff into all tenant databases
php artisan tenants:seed-demo --members=30
```

> **Re-seeding tip:** Use `php artisan tenants:seed-demo --fresh` to truncate and reseed tenant data, and `--force` on `tenants:seed-users` to reset passwords.

### Central Admin Credentials

All central users use password: **`password`**

| Email | Name | Role | Tenant Owner |
|---|---|---|---|
| `admin@sacco.test` | Super Admin | Super Admin (full access) | — |
| `user@sacco.test` | John Doe | User (tenant owner) | Testing SACCO |
| `jane@sacco.test` | Jane Achieng | User (tenant owner) | Kampala Teachers SACCO |
| `moses@sacco.test` | Moses Kasule | User (tenant owner) | Gulu Farmers Cooperative |
| `grace@sacco.test` | Grace Nambi | User (tenant owner) | Mbarara Women Savings Group |
| `david@sacco.test` | David Opio | User (tenant owner) | Jinja Traders Credit Union |
| `sarah@sacco.test` | Sarah Nakato | User (tenant owner) | Entebbe Microfinance Bank |

Access the admin panel at: `http://localhost:8000/admin`

### Tenant Panel Credentials

All tenant users use password: **`password`**

Each SACCO tenant gets 4 staff users seeded automatically:

| URL | Owner (Admin) | Manager | Staff | Teller |
|---|---|---|---|---|
| `kampala-teachers.localhost:8000/app` | `jane@sacco.test` | `manager@kampala-teachers.sacco` | `loans@kampala-teachers.sacco` | `teller@kampala-teachers.sacco` |
| `gulu-farmers.localhost:8000/app` | `moses@sacco.test` | `manager@gulu-farmers.sacco` | `loans@gulu-farmers.sacco` | `teller@gulu-farmers.sacco` |
| `mbarara-women.localhost:8000/app` | `grace@sacco.test` | `manager@mbarara-women.sacco` | `loans@mbarara-women.sacco` | `teller@mbarara-women.sacco` |
| `jinja-traders.localhost:8000/app` | `david@sacco.test` | `manager@jinja-traders.sacco` | `loans@jinja-traders.sacco` | `teller@jinja-traders.sacco` |
| `entebbe-mfb.localhost:8000/app` | `sarah@sacco.test` | `manager@entebbe-microfinance.sacco` | `loans@entebbe-microfinance.sacco` | `teller@entebbe-microfinance.sacco` |

**Role access matrix:**

| Role | Members | Savings/Loans | Teller Ops | Finance/Compliance | Staff Mgmt |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manager | ✅ | ✅ | ✅ | ✅ | ❌ |
| Staff | ✅ | ✅ | ✅ | ❌ | ❌ |
| Teller | ✅ | ✅ | ✅ | ❌ | ❌ |

### Seeded Central Data

| Entity | Count | Details |
|---|---|---|
| Users | 7 | Super admin + 5 SACCO owners + 1 regular |
| Plans | 3 | Starter ($49/mo), Growth ($149/mo), Enterprise ($399/mo) |
| Tenants | 6 | Testing + 5 Ugandan SACCOs |
| Subscriptions | 5 | Active subscriptions |
| Invoices | 4 | Paid invoices |
| Orders | 4 | Demo sign-up requests |

### Seeded Tenant Data (per SACCO, via `tenants:seed-demo`)

| Entity | Count | Details |
|---|---|---|
| Staff Users | 4 | Admin, Manager, Loans Officer, Teller |
| Members | 30 | ~65% active, ~20% applicant, ~8% dormant, ~5% suspended, ~2% exited |

### Useful Tenant Dev Commands

```bash
# Seed/reset all tenant admin users
php artisan tenants:seed-users                          # safe to re-run
php artisan tenants:seed-users --force                  # also resets passwords

# Seed demo members + staff into all tenants
php artisan tenants:seed-demo --members=50              # default: 30
php artisan tenants:seed-demo --fresh                   # truncate first

# Mark inactive members as dormant (EOD batch)
php artisan members:mark-dormant [--dry-run] [--days=180]

# Run tenant-specific migrations
php artisan tenants:migrate
php artisan tenants:migrate --tenants=kampala-teachers
```

---

## Usage

### Local Development with Valet (Recommended)
If you are using Valet:
1.  Park or link your directory: `valet link tenancy-starterkit`
2.  Your app is now available at `http://tenancy-starterkit.test`.

### Accessing the Central Admin
Visit `http://tenancy-starterkit.test/admin` (or your configured domain) and log in with the admin user you created.

### Creating a Tenant
1.  Navigate to the **Tenants** resource in the admin panel.
2.  Click **New Tenant**.
3.  Fill in the tenant details and assign a domain.
    *   *Tip: Use subdomains like `foo` to access `foo.tenancy-starterkit.test` automatically with Valet.*
4.  The system will automatically:
    -   Create a new database for the tenant.
    -   Run tenant-specific migrations.
    -   Create the domain record.

### Accessing a Tenant
Once a tenant is created (e.g., with id `foo`), you can access their application at:
`http://foo.tenancy-starterkit.test`

## Automating Tenant Provisioning with Payments (Stripe Example)

A key feature of a SaaS application is automatically provisioning a new tenant account after a successful payment. Here’s a high-level guide on how to implement this using Stripe and Laravel Cashier.

The core idea is to create a Stripe webhook that, upon a successful payment, creates a `Tenant` model, which in turn triggers the automatic provisioning jobs.

1.  **Install Laravel Cashier (Stripe)**
    ```bash
    composer require laravel/cashier
    php artisan cashier:install
    php artisan migrate
    ```

2.  **Configure Stripe**
    Add your Stripe keys and a webhook secret to your `.env` file.
    ```env
    STRIPE_KEY=pk_...
    STRIPE_SECRET=sk_...
    STRIPE_WEBHOOK_SECRET=whsec_...
    ```

3.  **Create a Webhook Controller**
    This controller will handle incoming events from Stripe.
    ```bash
    php artisan make:controller StripeWebhookController
    ```
    In your `StripeWebhookController.php`, create a method to handle the `checkout.session.completed` event. This is where the magic happens.

4.  **Define the Webhook Route**
    In `routes/web.php`, create a route that points to your new controller. Remember to exclude it from CSRF protection.
    ```php
    Route::post(
        'stripe/webhook',
        [StripeWebhookController::class, 'handleWebhook']
    )->name('cashier.webhook');
    ```

5.  **Implement the Controller Logic**
    Inside your `handleWebhook` method:
    -   Verify the webhook signature.
    -   Get the user and payment details from the Stripe session object.
    -   **Crucially, create the `Tenant` record.** You can pass user details or generate a unique ID.
        ```php
        // Example logic in your controller
        $session = $event->data['object'];
        $user = User::find($session->client_reference_id);

        $tenant = App\Models\Central\Tenant::create([
            'id' => 'tenant-' . uniqid(),
            'name' => $user->name . "'s Team",
            'owner_id' => $user->id,
        ]);
        
        $tenant->createDomain([
            'domain' => strtolower(str_replace(' ', '', $user->name))
        ]);
        ```

6.  **Automatic Provisioning**
    That's it! The `TenancyServiceProvider` is already configured to listen for the `TenantCreated` event. Once the `Tenant` model is created in the webhook, the service provider will automatically trigger the job pipeline to:
    -   Create the tenant's database.
    -   Run the tenant's migrations.
    -   Perform any other setup jobs you've defined.

7.  **Frontend Integration**
    You will need to build a UI for users to select a plan and initiate the Stripe Checkout session. The `app/Filament/Pages/PurchasePlan.php` is a great starting point for this.

## Testing

This project uses **Pest** for testing and comes with a foundational test suite to ensure the core multi-tenancy and Filament features are working as expected. We highly recommend you extend this test suite as you build out your application.

### Running Tests

```bash
# Run all tests
php artisan test

# Run tests with a compact report
php artisan test --compact

# Run a specific test file (e.g., feature test)
php artisan test tests/Feature/Central/TenantLifecycleTest.php

# Run unit tests
php artisan test --group=unit
```

### Existing Test Structure

-   **`tests/Feature/Central/`**: Contains feature tests for the central application, covering functionalities such as:
    -   `InvoiceTest.php`: Tests invoice-related features.
    -   `SubscriptionTest.php`: Tests subscription management.
    -   `TenantLifecycleTest.php`: Essential tests for tenant creation, migration, and deletion.
    -   `UserTest.php`: Tests user management and authentication.
-   **`tests/Feature/Tenant/AppTest.php`**: A basic feature test for the tenant-specific application, ensuring it loads correctly. This is a good starting point for your tenant-specific feature tests.
-   **`tests/Unit/ExampleTest.php`**: An example unit test.

### Extending Your Test Suite

-   **Central Application Tests:** When adding new features to your central admin panel (e.g., new Filament resources, custom pages, or central API endpoints), create new feature tests in `tests/Feature/Central/`.
-   **Tenant Application Tests:** For any features specific to the tenant application (e.g., tenant-specific models, controllers, or API endpoints), create feature tests in `tests/Feature/Tenant/`. Remember to leverage [Pest's multi-tenancy testing utilities](https://tenancyforlaravel.com/docs/v3/testing#testing-with-tenancy) when interacting with tenant contexts.
-   **Unit Tests:** For isolated logic (e.g., service classes, complex helper functions, or model methods without database interaction), create unit tests in `tests/Unit/`.

## Useful Documentation

-   **Laravel Tenancy (stancl/tenancy):** [https://tenancyforlaravel.com/docs/v3](https://tenancyforlaravel.com/docs/v3) - *The core multi-tenancy package.*
-   **Filament PHP:** [https://filamentphp.com/docs](https://filamentphp.com/docs) - *The admin panel framework.*
-   **Laravel Documentation:** [https://laravel.com/docs](https://laravel.com/docs) - *The PHP framework.*
-   **Livewire:** [https://livewire.laravel.com/docs](https://livewire.laravel.com/docs) - *Dynamic frontend components.*
-   **Tailwind CSS:** [https://tailwindcss.com/docs](https://tailwindcss.com/docs) - *Utility-first CSS framework.*

## Architecture Overview

### Central Context (`App\Models\Central`)
Models and logic related to the management of the SaaS platform itself.
-   **User:** Administrators of the central application.
-   **Tenant:** The entity representing a customer account.
-   **Domain:** The domain(s) associated with a tenant.
-   **Plan/Subscription/Invoice:** Billing-related models.

### Tenant Context (`App\Models\Tenant`)
Models and logic specific to each SACCO tenant's operations.
-   **Member Management:** `Member`, `MemberGroup`, `MemberDocument`, `MemberShare`, `MemberStateHistory`
-   **Savings & Deposits:** `SavingsProduct`, `SavingsAccount`, `SavingsTransaction`, `FixedDeposit`, `InterestAccrual`
-   **Lending:** `LoanProduct`, `Loan`, `LoanApplication`, `LoanApproval`, `LoanCollateral`, `LoanGuarantor`, `LoanRepayment`, `AmortisationSchedule`
-   **Accounting:** `ChartOfAccount`, `JournalEntry`, `JournalLine`, `AccountingPeriod`, `Budget`, `CostCentre`, `CostAllocation`
-   **Compliance & Risk:** `AmlAlert`, `KycScreening`, `CrbSubmission`, `CrbInquiry`, `EclComputation`, `EclStaging`, `BaselReport`
-   **Collections:** `CollectionsWorklist`, `CollectionsActivity`, `DemandLetter`, `LegalCase`, `EscalationChain`
-   **Operations:** `User`, `Agent`, `AgentTransaction`, `AtmTerminal`, `Card`, `NotificationTemplate`, `NotificationLog`
-   Migrations for tenant models go in `database/migrations/tenant`.

### Routing
-   `routes/web.php`: Routes for the Central Application (Landing page, Admin Panel).
-   `routes/tenant.php`: Routes for the Tenant Application (accessed via subdomain).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1.  Fork the project
2.  Create your feature branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request


## Custom Development Services

Need custom features, additional integrations, or professional assistance in developing your multi-tenant application?

I offer custom development services to help you bring your project vision to life. Whether it's implementing new features, performance optimization, or technical support, feel free to contact me.

**Contact me to discuss your project needs!**

-   **Email:** dani@quikpl.com

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).