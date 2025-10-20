<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpThrottle extends Model
{
    protected $fillable = [
        'ip_address',
        'fingerprint',
        'counts',
        'last_seen',
        'block_until',
        'total_hits',
        'country',
    ];
    
}
