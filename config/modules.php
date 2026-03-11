<?php

/**
 * HSMS Module Registry.
 *
 * Every tenant-side module is registered here. Plans reference these keys
 * in their `modules` JSON column to control which modules a tenant can access.
 */
return [
    'member_management' => [
        'label' => 'Member Management',
        'description' => 'Member registration, KYC, share capital, lifecycle management',
        'stage' => 1,
        'icon' => 'heroicon-o-users',
    ],

    'savings_deposits' => [
        'label' => 'Savings & Deposits',
        'description' => 'Savings products, accounts, deposits, withdrawals, interest, fixed deposits',
        'stage' => 1,
        'icon' => 'heroicon-o-banknotes',
    ],

    'loan_management' => [
        'label' => 'Loan Management',
        'description' => 'Loan products, origination, guarantors, collateral, repayments, PAR',
        'stage' => 1,
        'icon' => 'heroicon-o-currency-dollar',
    ],

    'general_ledger' => [
        'label' => 'General Ledger & Accounting',
        'description' => 'Chart of accounts, journal entries, trial balance, financial statements',
        'stage' => 1,
        'icon' => 'heroicon-o-calculator',
    ],

    'digital_channels' => [
        'label' => 'Digital & Hybrid Channels',
        'description' => 'Teller operations, mobile banking, USSD, agent banking, offline operations',
        'stage' => 1,
        'icon' => 'heroicon-o-device-phone-mobile',
    ],

    'notifications_engine' => [
        'label' => 'Notifications Engine',
        'description' => 'SMS, email, push notifications, templates, audit logs, staff alerts',
        'stage' => 1,
        'icon' => 'heroicon-o-bell-alert',
    ],

    'revenue_expense' => [
        'label' => 'Revenue & Expense Engine',
        'description' => 'Revenue sources, WHT, budgets, expense claims, investment portfolio',
        'stage' => 2,
        'icon' => 'heroicon-o-chart-pie',
    ],

    'cost_centres' => [
        'label' => 'Cost Centres & Profitability',
        'description' => 'Cost centre hierarchy, allocations, P&L per cost centre',
        'stage' => 2,
        'icon' => 'heroicon-o-building-office-2',
    ],

    'regulatory_compliance' => [
        'label' => 'Regulatory Compliance',
        'description' => 'Prudential returns, AML monitoring, CRB submissions, tax compliance',
        'stage' => 2,
        'icon' => 'heroicon-o-shield-check',
    ],

    'collections_engine' => [
        'label' => 'Collections Engine',
        'description' => 'Delinquency management, worklists, PTP tracking, legal cases, write-offs',
        'stage' => 2,
        'icon' => 'heroicon-o-exclamation-triangle',
    ],
];
