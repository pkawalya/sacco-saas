<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add security headers to all responses.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy — send origin only for cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Prevent exposure of permissions
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy — protect against XSS injection
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self';"
        );

        // HSTS — enforce HTTPS for 1 year (only in production)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Cache control for authenticated pages
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
