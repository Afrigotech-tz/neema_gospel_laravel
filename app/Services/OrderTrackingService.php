<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use App\Models\OrderAnalytics;
use Illuminate\Support\Facades\DB;

class OrderTrackingService
{
    /**
     * Update order status and create history record
     */
    public function updateOrderStatus(Order $order, string $newStatus, string $notes = null, int $changedBy = null)
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $newStatus, $previousStatus, $notes, $changedBy) {
            $order->update(['status' => $newStatus]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'previous_status' => $previousStatus,
                'notes' => $notes,
                'changed_by' => $changedBy,
            ]);

            // Update analytics when order is completed
            if ($newStatus === 'completed') {
                OrderAnalytics::updateDailyAnalytics($order->created_at->toDateString());
            }
        });

        return $order->fresh();
    }

    /**
     * Create shipment for an order
     */
    public function createShipment(Order $order, array $shipmentData)
    {
        return DB::transaction(function () use ($order, $shipmentData) {
            $shipment = $order->shipment()->create($shipmentData);

            // Update order status to shipped
            $this->updateOrderStatus(
                $order,
                'shipped',
                'Shipment created with tracking number: ' . $shipmentData['tracking_number']
            );

            return $shipment;
        });
    }

    /**
     * Get order tracking information
     */
    public function getOrderTrackingInfo(Order $order)
    {
        return [
            'order' => $order,
            'status_history' => $order->statusHistory()->with('changedBy')->latest()->get(),
            'shipment' => $order->shipment,
            'estimated_delivery' => $order->shipment?->estimated_delivery,
            'tracking_url' => $order->shipment?->tracking_url,
        ];
    }

    /**
     * Update shipment status
     */
    public function updateShipmentStatus(Shipment $shipment, string $status, array $trackingUpdates = [])
    {
        $shipment->update([
            'status' => $status,
            'tracking_updates' => array_merge($shipment->tracking_updates ?? [], $trackingUpdates),
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);

        // Update order status based on shipment status
        if ($status === 'delivered') {
            $this->updateOrderStatus(
                $shipment->order,
                'completed',
                'Order delivered successfully'
            );
        }

        return $shipment->fresh();
    }

    /**
     * Get order analytics for a date range
     */
    public function getOrderAnalytics($startDate, $endDate)
    {
        return OrderAnalytics::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get real-time order statistics
     */
    public function getRealTimeStats()
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'today_revenue' => Order::whereDate('created_at', today())->sum('total_amount'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
        ];

        
    }
}
