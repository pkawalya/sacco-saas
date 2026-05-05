<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict admin panel access to whitelisted IPs.
 *
 * Configure allowed IPs in config/security.php or .env.
 * When the whitelist is empty, all IPs are allowed.
 */
class IpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('security.admin_ip_whitelist', []);

        // If whitelist is empty, allow all (dev-friendly default)
        if (empty($allowedIps)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (! in_array($clientIp, $allowedIps)) {
            abort(403, 'Access denied. Your IP address is not authorised.');
        }

        return $next($request);
    }
}
