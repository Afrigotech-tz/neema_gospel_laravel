<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'service',
        'shipping_cost',
        'status',
        'shipped_at',
        'estimated_delivery',
        'delivered_at',
        'shipping_address',
        'notes',
        'tracking_updates',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'estimated_delivery' => 'datetime',
        'delivered_at' => 'datetime',
        'tracking_updates' => 'array',
        'shipping_cost' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isDelivered()
    {
        return $this->status === 'delivered' && $this->delivered_at !== null;
    }

    public function isShipped()
    {
        return $this->status === 'shipped' && $this->shipped_at !== null;
    }

    public function getTrackingUrlAttribute()
    {
        if (!$this->carrier || !$this->tracking_number) {
            return null;
        }

        $trackingUrls = [
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$this->tracking_number}",
            'ups' => "https://www.ups.com/track?tracknum={$this->tracking_number}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$this->tracking_number}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$this->tracking_number}",
        ];

        return $trackingUrls[strtolower($this->carrier)] ?? null;
    }
}
