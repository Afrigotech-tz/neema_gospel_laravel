<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\CartItem;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Get available payment methods.
     */
    public function paymentMethods()
    {
        $methods = PaymentMethod::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }

    /**
     * Process payment for cart items.
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = $request->user();

        // Get cart items
        $cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Calculate total
        $total = $cartItems->sum(function ($item) {
            return $item->quantity * ($item->variant ? $item->variant->price : $item->product->base_price);
        });

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $request->address_id,
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'total_amount' => $total,
            'status' => 'pending',
            'payment_method_id' => $request->payment_method_id,
            'notes' => $request->notes
        ]);

        // Create order items
        foreach ($cartItems as $item) {
            $price = $item->variant ? $item->variant->price : $item->product->base_price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'price' => $price,
                'total' => $item->quantity * $price
            ]);

            // Update stock
            if ($item->product_variant_id) {
                $item->variant->decrement('stock_quantity', $item->quantity);
            } else {
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }

        // Clear cart
        CartItem::where('user_id', $user->id)->delete();

        // Create transaction record
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'payment_method_id' => $request->payment_method_id,
            'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
            'amount' => $total,
            'status' => 'pending',
            'gateway_response' => [],
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order->load(['items.product', 'items.variant']),
                'transaction' => $transaction
            ]
        ]);
    }

    /**
     * Get user's orders.
     */
    public function orders(Request $request)
    {
        $user = $request->user();

        $orders = Order::with(['items.product', 'items.variant', 'paymentMethod', 'transaction'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get specific order details.
     */
    public function orderDetails($id)
    {
        $user = request()->user();

        $order = Order::with(['items.product', 'items.variant', 'paymentMethod', 'transaction'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $user = $request->user();
        $order = Order::where('user_id', $user->id)->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order
        ]);
    }
}
