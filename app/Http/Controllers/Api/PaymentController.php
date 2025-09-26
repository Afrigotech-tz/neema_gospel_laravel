<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payments/methods",
     *     tags={"Payment"},
     *     summary="Get available payment methods",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active payment methods",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/payments/orders/{id}/status",
     *     tags={"Payment"},
     *     summary="Update order status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","processing","shipped","delivered","cancelled"}, example="shipped")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
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

    /**
     * @OA\Post(
     *     path="/api/payments/tickets/process",
     *     tags={"Payment"},
     *     summary="Process payment for ticket orders",
     *     security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Payment for concert tickets")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket payment processed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ticket order already processed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function processTicketPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:ticket_orders,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:500'
        ]);

        $ticketOrder = \App\Models\TicketOrder::find($request->order_id);

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], 404);
        }

        if ($ticketOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order already processed'
            ], 400);
        }

        // Simulate payment processing here or integrate with payment gateway
        // For now, mark as paid
        $ticketOrder->update([
            'status' => 'paid',
            'payment_method' => $request->payment_method_id,
            'payment_ref' => 'TKT-' . strtoupper(\Illuminate\Support\Str::random(10)),
        ]);

        // Update sold count on ticket type
        $ticketOrder->ticketType->increment('sold', $ticketOrder->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Ticket payment processed successfully',
            'data' => $ticketOrder->load(['event', 'ticketType'])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/payments/tickets/{orderId}/confirm",
     *     tags={"Payment"},
     *     summary="Confirm ticket payment",
     *     description="Confirm ticket payment (webhook or manual confirmation)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Ticket order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_ref", type="string", example="TKT-ABC123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket payment confirmed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ticket order already processed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function confirmTicketPayment(Request $request, $orderId)
    {
        $ticketOrder = \App\Models\TicketOrder::find($orderId);

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], 404);
        }

        if ($ticketOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order already processed'
            ], 400);
        }

        $ticketOrder->update([
            'status' => 'paid',
            'payment_ref' => $request->payment_ref ?? $ticketOrder->payment_ref,
        ]);

        $ticketOrder->ticketType->increment('sold', $ticketOrder->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Ticket payment confirmed successfully',
            'data' => $ticketOrder->load(['event', 'ticketType'])
        ]);
    }


}

