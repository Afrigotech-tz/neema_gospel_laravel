<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'client_type',
        'is_active',
        'last_used_at',
        'expires_at',
        'rate_limit',
        'requests_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'requests_count' => 'integer',
    ];

    /**
     * Check if API key is valid
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Increment request count
     */
    public function incrementRequests(): void
    {
        $this->increment('requests_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if rate limit is exceeded
     */
    public function isRateLimitExceeded(): bool
    {
        if (!$this->rate_limit) {
            return false;
        }

        return $this->requests_count >= $this->rate_limit;
    }

    

}
