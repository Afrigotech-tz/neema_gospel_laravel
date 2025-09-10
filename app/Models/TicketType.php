<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity',
        'sold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'sold' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function orders()
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function getAvailableTicketsAttribute()
    {
        return $this->quantity - $this->sold;
    }

    public function isAvailable($requestedQuantity = 1)
    {
        return $this->available_tickets >= $requestedQuantity;
    }
}
