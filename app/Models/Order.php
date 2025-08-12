<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'address_id',
        'order_number',
        'subtotal',
        'tax',
        'shipping',
        'total_amount',
        'status',
        'payment_method',
        'payment_method_id',
        'payment_status',
        'transaction_id',
        'notes',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'string',
        'payment_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Order status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shipping address for the order.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * Get the payment method for the order.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the transaction for the order.
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(uniqid(), -4));

        return $prefix . $timestamp . $random;
    }

    /**
     * Scope for filtering orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering orders by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering orders by payment status
     */
    public function scopeByPaymentStatus($query, string $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        ]);
    }

    /**
     * Check if order can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_DELIVERED
            && $this->payment_status === self::PAYMENT_COMPLETED;
    }

    /**
     * Get order total with currency formatting
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2);
    }

    /**
     * Get order status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            default => 'Unknown',
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_PENDING => 'Pending',
            self::PAYMENT_COMPLETED => 'Completed',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded',
            default => 'Unknown',
        };
    }

    /**
     * Calculate order subtotal from items
     */
    public function calculateSubtotal(): float
    {
        return $this->items->sum('total');
    }

    /**
     * Calculate order tax
     */
    public function calculateTax(): float
    {
        return $this->calculateSubtotal() * 0.1; // 10% tax rate
    }

    /**
     * Update order totals
     */
    public function updateTotals(): void
    {
        $this->subtotal = $this->calculateSubtotal();
        $this->tax = $this->calculateTax();
        $this->total_amount = $this->subtotal + $this->tax + $this->shipping;
        $this->save();
    }
}
