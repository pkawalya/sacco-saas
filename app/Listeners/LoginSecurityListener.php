<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Handles post-login security tasks:
 * - Record login IP and timestamp
 * - Reset failed login attempts
 * - Send login notification for new IP/device
 */
class LoginSecurityListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $ip = request()->ip();

        $previousIp = $user->last_login_ip ?? null;

        // Update login tracking
        $user->forceFill([
            'last_login_ip' => $ip,
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        // Log the login
        Log::info('User login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
        ]);

        // Send notification if IP changed
        if (config('security.login_notification.enabled') && $previousIp && $previousIp !== $ip) {
            Log::warning('Login from new IP', [
                'user_id' => $user->id,
                'email' => $user->email,
                'previous_ip' => $previousIp,
                'new_ip' => $ip,
            ]);

            // You can send a mail notification here in production
            // Notification::send($user, new NewDeviceLoginNotification($ip));
        }
    }
}
