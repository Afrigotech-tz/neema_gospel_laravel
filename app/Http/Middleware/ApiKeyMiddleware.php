<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
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
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
                'error' => 'Missing X-API-Key header or api_key query parameter'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
                'error' => 'The provided API key is not valid'
            ], 401);
        }

        if (!$key->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'API key is expired or inactive',
                'error' => 'This API key is no longer valid'
            ], 401);
        }

        if ($key->isRateLimitExceeded()) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'error' => 'You have exceeded the request limit for this API key'
            ], 429);
        }

        // Log the request
        $key->incrementRequests();

        // Add client info to request for controllers
        $request->merge([
            'api_client_type' => $key->client_type,
            'api_key_id' => $key->id,
        ]);

        return $next($request);

    }


    
}

