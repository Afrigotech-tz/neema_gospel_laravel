<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;



class IpBlacklist extends Model
{
    protected $fillable = ['ip', 'reason', 'ban_seconds', 'banned_at', 'expires_at'];

    protected $dates = ['banned_at', 'expires_at'];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->lessThan(now());
    }

    public static function addTemporary(string $ip, int $seconds, string $reason = null)
    {

        $expires = now()->addSeconds($seconds);
        return static::create([
            'ip' => $ip,
            'ban_seconds' => $seconds,
            'reason' => $reason,
            'banned_at' => now(),
            'expires_at' => $expires,
        ]);


    }

    public static function addPermanent(string $ip, string $reason = null)
    {
        return static::create([
            'ip' => $ip,
            'ban_seconds' => null,
            'reason' => $reason,
            'banned_at' => now(),
            'expires_at' => null,
        ]);
    }


}




