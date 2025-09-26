<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\PaymentMethod;
use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;



class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Get list of orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $orders = Order::with(['user', 'address', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);


        return response()->json([
            'success' => true,
            'data' => $orders
        ]);

    }

    /**
     * @OA\Post(
     *     path="/api/orders/process",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_id"},
     *             @OA\Property(property="address_id", type="integer", example=1),
     *             @OA\Property(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"product_id","quantity"},
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Please deliver between 9am-5pm")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient stock"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create order"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

             /** @var \App\Models\User $user */
             $user = auth()->user();

            // Get the address
            $address = Address::findOrFail($request->address_id);

            // Calculate total amount
            $totalAmount = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->name}"
                    ], 400);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . time() . '-' . strtoupper(substr(uniqid(), -4)),
                'address_id' => $address->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method_id' => $request->payment_method_id,
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($items as $item) {
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
                $orderItem->save();

                // Update product stock
                $product = Product::find($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }

            // Send confirmation email
            Mail::to($user->email)->send(new OrderConfirmationMail($order));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load('items', 'address')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    
    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function show($id)
    {
        $order = Order::with(['user', 'address', 'items.product', 'paymentMethod'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     tags={"Orders"},
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
     *             @OA\Property(property="status", type="string", enum={"pending","processing","shipped","delivered","cancelled"}, example="processing"),
     *             @OA\Property(property="notes", type="string", example="Order is being processed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order->update([
            'status' => $request->status,
            'notes' => $request->notes ?? $order->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/user",
     *     tags={"Orders"},
     *     summary="Get orders for authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function userOrders()
    {
        $orders = auth()->user()->orders()
            ->with(['address', 'items.product','paymentMethod', 'transaction'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
        
    }




     /**
     * @OA\Post(
     *     path="/api/payments/process",
     *     tags={"Payment"},
     *     summary="Process payment for cart items",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_id","payment_method_id"},
     *             @OA\Property(property="address_id", type="integer", example=1),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Please deliver between 9-12 PM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object"),
     *                 @OA\Property(property="transaction", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cart is empty or validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function processOrder(Request $request)
    {
        $request->validate([

            'address_id' => 'required|exists:addresses,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
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
            'subtotal' => $total,
            'tax' => 0,
            'shipping' => 0,
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
                'product_name' => $item->product->name,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'price' => $price,
                'total' => $item->quantity * $price
            ]);

            // Update stock
            if ($item->product_variant_id) {
                $item->variant->decrement('stock', $item->quantity);
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

        // Send confirmation email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OrderConfirmationMail($order));
        } catch (\Exception $e) {
            // Log email sending error but don't fail the order
            \Illuminate\Support\Facades\Log::error('Failed to send order confirmation email: ' . $e->getMessage());
        }

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
     * @OA\Get(
     *     path="/api/payments/orders",
     *     tags={"Payment"},
     *     summary="Get user's orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user's orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/payments/orders/{id}",
     *     tags={"Payment"},
     *     summary="Get specific order details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
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
    

}


