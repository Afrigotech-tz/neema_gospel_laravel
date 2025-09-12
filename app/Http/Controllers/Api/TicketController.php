<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tickets/events/{eventId}/ticket-types",
     *     tags={"Tickets"},
     *     summary="Get ticket types for an event",
     *     @OA\Parameter(
     *         name="eventId",
     *         in="path",
     *         description="ID of the event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of ticket types",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     )
     * )
     */
    public function getTicketTypes($eventId)
    {
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $ticketTypes = $event->ticketTypes()->get();

        return response()->json([
            'success' => true,
            'data' => $ticketTypes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tickets/purchase",
     *     tags={"Tickets"},
     *     summary="Purchase tickets",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_id","ticket_type_id","quantity","payment_method"},
     *             @OA\Property(property="event_id", type="integer", example=1),
     *             @OA\Property(property="ticket_type_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="guest_email", type="string", format="email", example="guest@example.com"),
     *             @OA\Property(property="guest_phone", type="string", example="+255712345678"),
     *             @OA\Property(property="payment_method", type="string", example="card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors or insufficient tickets"
     *     )
     * )
     */
    public function purchaseTickets(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string',
            'payment_method' => 'required|string'
        ]);

        $ticketType = TicketType::find($request->ticket_type_id);

        if (!$ticketType || $ticketType->event_id != $request->event_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ticket type'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$ticketType->isAvailable($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();
        $totalPrice = $ticketType->price * $request->quantity;

        // For unauthenticated users, require email or phone
        if (!$user && (!$request->guest_email && !$request->guest_phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Email or phone is required for guest purchases'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create ticket order
        $ticketOrder = TicketOrder::create([
            'event_id' => $request->event_id,
            'ticket_type_id' => $request->ticket_type_id,
            'user_id' => $user ? $user->id : null,
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'payment_ref' => 'TKT-' . Str::upper(Str::random(10))
        ]);

        // Note: Payment processing should be done via /api/tickets/process-payment endpoint
        // This endpoint creates the order, then payment should be processed separately

        return response()->json([
            'success' => true,
            'message' => 'Ticket order created successfully. Please process payment to complete purchase.',
            'data' => [
                'order' => $ticketOrder->load(['event', 'ticketType']),
                'payment_ref' => $ticketOrder->payment_ref,
                'next_step' => 'Call /api/tickets/process-payment with order_id to complete payment'
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tickets/orders/{orderId}/confirm-payment",
     *     tags={"Tickets"},
     *     summary="Confirm ticket payment",
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID of the ticket order",
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
     *         description="Payment confirmed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order already processed"
     *     )
     * )
     */
    public function confirmPayment(Request $request, $orderId)
    {
        $ticketOrder = TicketOrder::find($orderId);

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($ticketOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order already processed'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Update order status
        $ticketOrder->update([
            'status' => 'paid',
            'payment_ref' => $request->payment_ref ?? $ticketOrder->payment_ref
        ]);

        // Update ticket type sold count
        $ticketOrder->ticketType->increment('sold', $ticketOrder->quantity);

        // Generate e-tickets (you can implement QR code generation here)
        // Send confirmation email/SMS

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully',
            'data' => $ticketOrder->load(['event', 'ticketType'])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/my-orders",
     *     tags={"Tickets"},
     *     summary="Get user's ticket orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's ticket orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getUserTickets(Request $request)
    {
        $user = $request->user();

        $orders = TicketOrder::with(['event', 'ticketType'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/events/{eventId}/sales",
     *     tags={"Tickets"},
     *     summary="Get event sales summary",
     *     @OA\Parameter(
     *         name="eventId",
     *         in="path",
     *         description="ID of the event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event sales summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_tickets_sold", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number"),
     *                 @OA\Property(property="ticket_types", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     )
     * )
     */
    public function getEventSales($eventId)
    {
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $sales = [
            'total_tickets_sold' => $event->total_tickets_sold,
            'total_revenue' => $event->total_revenue,
            'ticket_types' => $event->ticketTypes()->with(['orders' => function($query) {
                $query->where('status', 'paid');
            }])->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/orders/{orderId}",
     *     tags={"Tickets"},
     *     summary="Get specific ticket order details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID of the ticket order",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket order details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found"
     *     )
     * )
     */
    public function getTicketOrder($orderId, Request $request)
    {
        $user = $request->user();
        $ticketOrder = TicketOrder::with(['event', 'ticketType'])
            ->where('id', $orderId)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('guest_email', $user->email);
            })
            ->first();

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $ticketOrder
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/tickets/orders/{orderId}",
     *     tags={"Tickets"},
     *     summary="Cancel ticket order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="ID of the ticket order",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket order cancelled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel order that is already processed"
     *     )
     * )
     */
    public function cancelTicketOrder($orderId, Request $request)
    {
        $user = $request->user();
        $ticketOrder = TicketOrder::where('id', $orderId)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('guest_email', $user->email);
            })
            ->first();

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($ticketOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order that is already processed'
            ], Response::HTTP_BAD_REQUEST);
        }

        $ticketOrder->update(['status' => 'cancelled']);

        // Restore ticket availability
        $ticketOrder->ticketType->decrement('sold', $ticketOrder->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Ticket order cancelled successfully',
            'data' => $ticketOrder
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/payment/{paymentRef}",
     *     tags={"Tickets"},
     *     summary="Get ticket order by payment reference",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentRef",
     *         in="path",
     *         description="Payment reference of the ticket order",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket order details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket order not found"
     *     )
     * )
     */
    public function getTicketByPaymentRef($paymentRef, Request $request)
    {
        $user = $request->user();
        $ticketOrder = TicketOrder::with(['event', 'ticketType'])
            ->where('payment_ref', $paymentRef)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('guest_email', $user->email);
            })
            ->first();

        if (!$ticketOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $ticketOrder
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tickets/events/{eventId}/ticket-types",
     *     tags={"Tickets"},
     *     summary="Create a new ticket type for an event",
     *     @OA\Parameter(
     *         name="eventId",
     *         in="path",
     *         description="ID of the event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","quantity"},
     *             @OA\Property(property="name", type="string", example="VIP Ticket"),
     *             @OA\Property(property="description", type="string", example="VIP access with premium seating"),
     *             @OA\Property(property="price", type="number", example=100.00),
     *             @OA\Property(property="quantity", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket type created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors"
     *     )
     * )
     */
    public function createTicketType(Request $request, $eventId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1'
        ]);

        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // TODO: Add authorization check to ensure user can manage this event

        $ticketType = TicketType::create([
            'event_id' => $eventId,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'sold' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket type created successfully',
            'data' => $ticketType->load('event')
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/api/tickets/ticket-types/{ticketTypeId}",
     *     tags={"Tickets"},
     *     summary="Update a ticket type",
     *     @OA\Parameter(
     *         name="ticketTypeId",
     *         in="path",
     *         description="ID of the ticket type",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated VIP Ticket"),
     *             @OA\Property(property="description", type="string", example="Updated VIP access with premium seating"),
     *             @OA\Property(property="price", type="number", example=120.00),
     *             @OA\Property(property="quantity", type="integer", example=60)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket type updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket type not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors or cannot reduce quantity below sold tickets"
     *     )
     * )
     */
    public function updateTicketType(Request $request, $ticketTypeId)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:1'
        ]);

        $ticketType = TicketType::find($ticketTypeId);

        if (!$ticketType) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket type not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // TODO: Add authorization check to ensure user can manage this ticket type's event

        // Check if updating quantity would be valid (can't reduce below sold tickets)
        if ($request->has('quantity') && $request->quantity < $ticketType->sold) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reduce quantity below already sold tickets'
            ], Response::HTTP_BAD_REQUEST);
        }

        $ticketType->update($request->only(['name', 'description', 'price', 'quantity']));

        return response()->json([
            'success' => true,
            'message' => 'Ticket type updated successfully',
            'data' => $ticketType->load('event')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/tickets/ticket-types/{ticketTypeId}",
     *     tags={"Tickets"},
     *     summary="Delete a ticket type",
     *     @OA\Parameter(
     *         name="ticketTypeId",
     *         in="path",
     *         description="ID of the ticket type",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket type deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket type not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete ticket type with existing paid orders"
     *     )
     * )
     */
    public function deleteTicketType($ticketTypeId)
    {
        $ticketType = TicketType::find($ticketTypeId);

        if (!$ticketType) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket type not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // TODO: Add authorization check to ensure user can manage this ticket type's event

        // Check if there are any paid orders for this ticket type
        $hasPaidOrders = $ticketType->orders()->where('status', 'paid')->exists();

        if ($hasPaidOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ticket type with existing paid orders'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Cancel any pending orders
        $ticketType->orders()->where('status', 'pending')->update(['status' => 'cancelled']);

        $ticketType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket type deleted successfully'
        ]);
    }
}
