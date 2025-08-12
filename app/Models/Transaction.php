<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'notes',
        'payment_reference',
        'phone_number',
        'account_number',
        'response_data',
        'error_message',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency' => 'string',
        'gateway_response' => 'array',
        'response_data' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
