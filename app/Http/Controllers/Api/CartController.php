<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    /**
     * Display the user's cart.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', $user->id)
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * ($item->variant ? $item->variant->price : $item->product->base_price);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total' => $total,
                'count' => $cartItems->count()
            ]
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $product = Product::find($request->product_id);

        // Check if product is active
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check stock availability
        $availableStock = $request->product_variant_id
            ? ProductVariant::find($request->product_variant_id)->stock_quantity
            : $product->stock_quantity;

        if ($availableStock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if item already exists in cart
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($availableStock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for the requested quantity'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'product_variant_id' => $request->product_variant_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data' => $cartItem->load(['product', 'variant'])
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $cartItem = CartItem::where('user_id', $user->id)->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $product = $cartItem->product;
        $availableStock = $cartItem->product_variant_id
            ? $cartItem->variant->stock_quantity
            : $product->stock_quantity;

        if ($availableStock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], Response::HTTP_BAD_REQUEST);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated',
            'data' => $cartItem->load(['product', 'variant'])
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = CartItem::where('user_id', $user->id)->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    /**
     * Clear the user's cart.
     */
    public function clear(Request $request)
    {
        $user = $request->user();

        CartItem::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}
