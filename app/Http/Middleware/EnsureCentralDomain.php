<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access when the request comes from a tenant subdomain.
 *
 * Handles localhost / 127.0.0.1 equivalence so the admin panel
 * works regardless of which address the developer uses.
 */
class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $centralDomain = config('tenancy.central_domain', 'localhost');

        // Strip port (e.g. "localhost:8000" → "localhost")
        $centralHost = parse_url('http://'.$centralDomain, PHP_URL_HOST) ?: 'localhost';

        $allowed = [$centralHost];

        // Treat localhost and 127.0.0.1 as interchangeable
        if ($centralHost === 'localhost') {
            $allowed[] = '127.0.0.1';
        } elseif ($centralHost === '127.0.0.1') {
            $allowed[] = 'localhost';
        }

        if (! in_array($request->getHost(), $allowed, true)) {
            abort(404);
        }

        return $next($request);
    }
}
