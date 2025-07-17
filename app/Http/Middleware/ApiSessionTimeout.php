<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class ApiSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());

            if ($token) {
                $user = $token->tokenable;
                $cacheKey = 'user_last_activity_' . $user->id;
                $lastActivity = Cache::get($cacheKey);
                $timeout = config('session.lifetime') * 60; // Convert minutes to seconds

                if ($lastActivity && (time() - $lastActivity > $timeout)) {
                    // Revoke the token
                    $token->delete();
                    return response()->json([
                        'message' => 'Session expired due to inactivity.',
                        'error' => 'session_timeout'
                    ], 401);
                }

                // Update last activity time
                Cache::put($cacheKey, time(), config('session.lifetime'));
            }
        }

        return $next($request);
    }
}
