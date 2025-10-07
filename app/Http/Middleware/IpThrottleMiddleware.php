<?php

namespace App\Http\Middleware;

use App\Models\IpThrottle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class IpThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     *
     */

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Allow 5 requests per minute per IP
        $maxAttempts = 5;
        $decayMinutes = 1;

        $throttle = IpThrottle::where('ip_address', $ip)->first();

        if (!$throttle) {
            $throttle = new IpThrottle();
            $throttle->ip_address = $ip;
            $throttle->last_seen = Carbon::now();
            $throttle->save();
        }

        // Increment total hits
        $throttle->increment('total_hits');

        // Check if blocked
        if ($throttle->block_until && Carbon::now()->lessThan($throttle->block_until)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => Carbon::now()->diffInSeconds($throttle->block_until)
            ], 429);
        }

        // Reset counts if window has passed
        if ($throttle->last_seen && Carbon::now()->diffInMinutes($throttle->last_seen) >= $decayMinutes) {
            $throttle->counts = 0;
        }

        // Increment counts and update last_seen
        $throttle->increment('counts');
        $throttle->last_seen = Carbon::now();

        // Check if exceeded
        if ($throttle->counts > $maxAttempts) {
            $throttle->block_until = Carbon::now()->addMinutes($decayMinutes);
        }

        $throttle->save();

        return $next($request);
        
    }

}




