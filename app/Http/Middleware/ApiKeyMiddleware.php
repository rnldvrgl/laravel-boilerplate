<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Keep the shared API secret in the environment so each deployment can set its own value.
        $expectedKey = config('app.api_auth_key');

        if (blank($expectedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API authentication is not configured.',
            ], 500);
        }

        // Require the same key on protected requests to reduce accidental exposure.
        $providedKey = $request->header('X-API-Key');

        if (! \is_string($providedKey) || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
