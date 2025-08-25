<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAnalytics extends Model
{
    use HasFactory;

    protected $table = 'order_analytics';

    protected $fillable = [
        'date',
        'total_orders',
        'total_revenue',
        'completed_orders',
        'pending_orders',
        'cancelled_orders',
        'average_order_value',
        'unique_customers',
        'top_products',
        'payment_method_stats',
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'top_products' => 'array',
        'payment_method_stats' => 'array',
    ];

    public static function updateDailyAnalytics($date = null)
    {
        $date = $date ?? now()->toDateString();

        $orders = \App\Models\Order::whereDate('created_at', $date);

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $completedOrders = $orders->where('status', 'completed')->count();
        $pendingOrders = $orders->where('status', 'pending')->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();
        $uniqueCustomers = $orders->distinct('user_id')->count('user_id');

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Get top products for the day
        $topProducts = \App\Models\OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->whereDate('created_at', $date)
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->pluck('total_quantity', 'product_name')
            ->toArray();

        // Get payment method statistics
        $paymentStats = \App\Models\Order::select('payment_method_id')
            ->selectRaw('COUNT(*) as count')
            ->whereDate('created_at', $date)
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name')
            ->get()
            ->mapWithKeys(function ($order) {
                return [$order->paymentMethod->name => $order->count];
            })
            ->toArray();

        static::updateOrCreate(
            ['date' => $date],
            [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'completed_orders' => $completedOrders,
                'pending_orders' => $pendingOrders,
                'cancelled_orders' => $cancelledOrders,
                'average_order_value' => $averageOrderValue,
                'unique_customers' => $uniqueCustomers,
                'top_products' => $topProducts,
                'payment_method_stats' => $paymentStats,
            ]
        );
    }
}
