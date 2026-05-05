<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-logout after inactivity period (default: 15 minutes).
 */
class IdleTimeout
{
    protected int $timeoutMinutes = 15;

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $lastActivity = session('last_activity');

        if ($lastActivity && (time() - $lastActivity) > ($this->timeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')
                ->with('warning', 'You have been logged out due to inactivity.');
        }

        session(['last_activity' => time()]);

        return $next($request);
    }
}
