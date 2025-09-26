<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderTrackingService;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    protected $orderTrackingService;

    public function __construct(OrderTrackingService $orderTrackingService)
    {
        $this->orderTrackingService = $orderTrackingService;
    }

    /**
     * Get order tracking information
     */
    public function trackOrder($orderId)
    {
        $order = Order::with(['user', 'items', 'shipment', 'statusHistory.changedBy'])
            ->findOrFail($orderId);

        return response()->json([
            'order' => $order,
            'tracking_info' => $this->orderTrackingService->getOrderTrackingInfo($order),
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($orderId);

        $updatedOrder = $this->orderTrackingService->updateOrderStatus(
            $order,
            $request->status,
            $request->notes,
            auth()->id()
        );

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $updatedOrder,
            'status_history' => $updatedOrder->statusHistory()->latest()->get(),
        ]);
    }

    /**
     * Create shipment for order
     */
    public function createShipment(Request $request, $orderId)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255',
            'carrier' => 'required|string|max:100',
            'estimated_delivery' => 'required|date',
            'shipping_address' => 'required|string|max:500',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($orderId);

        // Check if shipment already exists
        if ($order->shipment) {
            return response()->json([
                'message' => 'Shipment already exists for this order',
            ], 422);
        }

        $shipment = $this->orderTrackingService->createShipment($order, [
            'tracking_number' => $request->tracking_number,
            'carrier' => $request->carrier,
            'estimated_delivery' => $request->estimated_delivery,
            'shipping_address' => $request->shipping_address,
            'shipping_cost' => $request->shipping_cost ?? 0,
            'notes' => $request->notes,
            'status' => 'processing',
        ]);

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => $shipment,
        ]);
    }

    /**
     * Update shipment status
     */
    public function updateShipmentStatus(Request $request, $shipmentId)
    {
        $request->validate([
            'status' => 'required|string|in:processing,in_transit,out_for_delivery,delivered,failed',
            'tracking_updates' => 'nullable|array',
            'tracking_updates.*' => 'string',
        ]);

        $shipment = Shipment::findOrFail($shipmentId);

        $updatedShipment = $this->orderTrackingService->updateShipmentStatus(
            $shipment,
            $request->status,
            $request->tracking_updates ?? []
        );

        return response()->json([
            'message' => 'Shipment status updated successfully',
            'shipment' => $updatedShipment,
        ]);


    }

    /**
     * Get order analytics
     */

    public function getAnalytics(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $analytics = $this->orderTrackingService->getOrderAnalytics(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get real-time statistics
     */
    public function getRealTimeStats()
    {
        $stats = $this->orderTrackingService->getRealTimeStats();

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Get customer order history
     */
    public function getCustomerOrders($customerId)
    {
        $orders = Order::with(['items', 'shipment'])
            ->where('user_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Search orders
     */
    public function searchOrders(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'status' => 'nullable|string|in:pending,processing,shipped,completed,cancelled',
        ]);

        $query = Order::with(['user', 'shipment'])
            ->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->query}%")
                    ->orWhereHas('user', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->query}%")
                            ->orWhere('email', 'like', "%{$request->query}%");
                    });
            });

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'orders' => $orders,
        ]);
    }


}

