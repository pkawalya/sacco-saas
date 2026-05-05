<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force password change on first login or expired passwords (90-day rotation).
 */
class EnforcePasswordPolicy
{
    protected int $passwordExpiryDays = 90;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Skip for password-change routes
        if ($request->routeIs('password.change*') || $request->is('*/change-password*')) {
            return $next($request);
        }

        // Force password change on first login
        if ($user->must_change_password ?? false) {
            return redirect()->route('filament.admin.pages.change-password')
                ->with('warning', 'You must change your password before continuing.');
        }

        // Check password expiry (90 days)
        $changedAt = $user->password_changed_at;
        if ($changedAt && $changedAt->diffInDays(now()) >= $this->passwordExpiryDays) {
            return redirect()->route('filament.admin.pages.change-password')
                ->with('warning', 'Your password has expired. Please set a new password.');
        }

        return $next($request);
    }
}
