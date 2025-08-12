<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'amount',
        'currency',
        'reason',
        'status',
        'gateway_response',
        'gateway_refund_id',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeProcessed(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PROCESSING]);
    }

    public function markAsProcessed(string $gatewayResponse = null, string $gatewayRefundId = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'gateway_response' => $gatewayResponse,
            'gateway_refund_id' => $gatewayRefundId,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);
    }

    public function markAsFailed(string $errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_response' => $errorMessage,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);
    }

    public function getTotalRefundedAmount(): float
    {
        return $this->items->sum('total_amount') ?: $this->amount;
    }
}
