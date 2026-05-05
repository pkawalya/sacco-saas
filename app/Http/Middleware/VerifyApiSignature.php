<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify HMAC signature on API requests.
 *
 * Expects headers:
 *  - X-Api-Key: the client's API key
 *  - X-Signature: HMAC-SHA256(request body, secret)
 *  - X-Timestamp: Unix timestamp (must be within 5 minutes)
 */
class VerifyApiSignature
{
    protected int $timestampToleranceSeconds = 300; // 5 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (! $apiKey || ! $signature || ! $timestamp) {
            abort(401, 'Missing API authentication headers.');
        }

        // Check timestamp freshness
        if (abs(time() - (int) $timestamp) > $this->timestampToleranceSeconds) {
            abort(401, 'Request timestamp expired.');
        }

        // Look up the API secret for this key
        $secret = config("security.api_keys.{$apiKey}");

        if (! $secret) {
            abort(401, 'Invalid API key.');
        }

        // Verify HMAC signature
        $payload = $timestamp.'.'.$request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            abort(401, 'Invalid request signature.');
        }

        return $next($request);
    }
}
