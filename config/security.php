<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        'max_attempts' => (int) env('SECURITY_LOCKOUT_MAX_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('SECURITY_LOCKOUT_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */
    'password' => [
        'expiry_days' => (int) env('SECURITY_PASSWORD_EXPIRY_DAYS', 90),
        'min_length' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Idle Timeout
    |--------------------------------------------------------------------------
    */
    'idle_timeout_minutes' => (int) env('SECURITY_IDLE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel IP Whitelist
    |--------------------------------------------------------------------------
    | Leave empty to allow all IPs (development).
    | In production, populate with office/VPN IPs.
    */
    'admin_ip_whitelist' => array_filter(explode(',', env('SECURITY_ADMIN_IPS', ''))),

    /*
    |--------------------------------------------------------------------------
    | API Keys for HMAC Signing
    |--------------------------------------------------------------------------
    | Format: 'api_key' => 'secret'
    */
    'api_keys' => [
        // 'mobile-app-v1' => env('API_SECRET_MOBILE', ''),
        // 'partner-bank' => env('API_SECRET_PARTNER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Notifications
    |--------------------------------------------------------------------------
    */
    'login_notification' => [
        'enabled' => (bool) env('SECURITY_LOGIN_NOTIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup Encryption
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY', ''),
        'storage_path' => storage_path('app/backups'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        'required_for_roles' => ['admin', 'super_admin'],
    ],

];
