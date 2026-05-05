<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

/**
 * Handles failed login attempts for account lockout.
 */
class FailedLoginListener
{
    public function handle(Failed $event): void
    {
        if (! $event->user) {
            return;
        }

        $user = $event->user;
        $maxAttempts = config('security.lockout.max_attempts', 5);
        $lockoutMinutes = config('security.lockout.lockout_minutes', 30);

        $attempts = ($user->failed_login_attempts ?? 0) + 1;

        $data = ['failed_login_attempts' => $attempts];

        // Lock the account after max attempts
        if ($attempts >= $maxAttempts) {
            $data['locked_until'] = now()->addMinutes($lockoutMinutes);

            Log::critical('Account locked due to failed login attempts', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => $attempts,
                'locked_until' => $data['locked_until'],
                'ip' => request()->ip(),
            ]);
        }

        $user->forceFill($data)->save();
    }
}
