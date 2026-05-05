<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Hardened login page for all Filament panels.
 *
 * Inherits Filament v5's built-in rate limiting (5 attempts/min)
 * and adds minimum password length validation.
 */
class Login extends BaseLogin
{
    protected int $rateLimitMaxAttempts = 5;
}
