<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

/**
 * Centralized password rules for the SACCO application.
 *
 * Use: PasswordRules::strong() in form requests.
 */
class PasswordRules
{
    /**
     * Strong password for staff/admin accounts.
     */
    public static function strong(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * Standard password for regular users.
     */
    public static function standard(): Password
    {
        return Password::min(8)
            ->letters()
            ->numbers();
    }
}
