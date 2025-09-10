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
     * Get ticket types for an event.
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
     * Purchase tickets.
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
     * Confirm payment (webhook or manual confirmation).
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
     * Get user's ticket orders.
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
     * Get event sales summary.
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
     * Get specific ticket order details.
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
     * Cancel ticket order.
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
     * Get ticket order by payment reference.
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
     * Create a new ticket type for an event.
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
     * Update a ticket type.
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
     * Delete a ticket type.
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
