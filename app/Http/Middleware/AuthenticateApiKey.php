<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SchoolApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API key authentication for the stats API.
 * Reads `Authorization: Bearer <raw_key>`, hashes it, resolves the key + school.
 */
final class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->bearerToken();

        if (! $rawKey) {
            return response()->json(['error' => __('api.missing_key')], 401);
        }

        $apiKey = SchoolApiKey::findByKey($rawKey);

        if ($apiKey === null) {
            return response()->json(['error' => __('api.invalid_key')], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json(['error' => __('api.key_expired')], 401);
        }

        // Attach the resolved key to the request for downstream controllers
        $request->attributes->set('api_key', $apiKey);

        // Non-blocking last_used_at update
        $apiKey->timestamps = false;
        $apiKey->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
