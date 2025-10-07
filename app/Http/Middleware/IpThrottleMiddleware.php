<?php

namespace App\Http\Middleware;

use App\Models\IpThrottle;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Torann\GeoIP\Facades\GeoIP;

class IpThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * 
     */
    
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Maximum requests allowed per window
        $maxAttempts = 600;
        // Window duration in minutes
        $decayMinutes = 1;

        $throttle = IpThrottle::where('ip_address', $ip)->first();

        if (!$throttle) {
            // Create a new record for this IP
            $throttle = new IpThrottle();
            $throttle->ip_address = $ip;
            $throttle->counts = 0;
            $throttle->total_hits = 0;
            $throttle->last_seen = Carbon::now();
            $throttle->block_until = null;
            $throttle->country = $this->getCountryFromIP($ip);
            $throttle->save();
            
        } elseif (!$throttle->country) {
            // Update existing record if country is null
            $throttle->country = $this->getCountryFromIP($ip);
            $throttle->save();
        }

        // Increment total hits regardless
        $throttle->increment('total_hits');

        $now = Carbon::now();

        // Reset counts if block has expired
        if ($throttle->block_until && $now->greaterThanOrEqualTo($throttle->block_until)) {
            $throttle->counts = 0;
            $throttle->block_until = null;
        }

        // Check if currently blocked
        if ($throttle->block_until && $now->lessThan($throttle->block_until)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $now->diffInSeconds($throttle->block_until)
            ], 429);
        }

        // Reset counts if decay window has passed since last_seen
        if ($throttle->last_seen && $now->diffInMinutes($throttle->last_seen) >= $decayMinutes) {
            $throttle->counts = 0;
        }

        // Increment counts and update last_seen
        $throttle->counts++;
        $throttle->last_seen = $now;

        // Block IP if exceeded max attempts
        if ($throttle->counts > $maxAttempts) {
            $throttle->block_until = $now->copy()->addMinutes($decayMinutes);
        }

        $throttle->save();

        return $next($request);

    }

    /**
     * Get country name from IP address using GeoIP
     *
     * @param string $ip
     * @return string|null
     */
    private function getCountryFromIP(string $ip): ?string
    {
        try {
            $location = GeoIP::getLocation($ip);
            return $location['country'] ?? null;
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::warning("Failed to get country for IP {$ip}: " . $e->getMessage());
            return null;
        }
    }

}



