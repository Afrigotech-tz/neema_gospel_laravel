<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Add a new address for the authenticated user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:billing,shipping',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // If this is set as default, unset other default addresses
        if ($request->is_default) {
            Address::where('user_id', Auth::id())
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'is_default' => $request->is_default ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully',
            'data' => $address
        ], 201);
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|in:billing,shipping',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'address_line_1' => 'sometimes|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:255',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // If setting as default, unset other default addresses
        if ($request->has('is_default') && $request->is_default) {
            Address::where('user_id', Auth::id())
                ->where('type', $address->type)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy($id)
    {
        $address = Address::where('user_id', Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        // Prevent deletion of default address
        if ($address->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default address. Please set another address as default first.'
            ], 400);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }

    /**
     * Get all addresses for the authenticated user
     */
    public function index()
    {
        $addresses = Address::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Get a specific address
     */
    public function show($id)
    {
        $address = Address::where('user_id', Auth::id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }

    /**
     * Get addresses for a specific user
     */
    public function getUserAddresses($userId)
    {
        // Only allow users to view their own addresses
        if (Auth::id() != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view addresses for this user'
            ], 403);
        }

        $addresses = Address::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Set a default address for a user
     */
    public function setDefaultAddress(Request $request, $userId)
    {
        // Only allow users to modify their own addresses
        if (Auth::id() != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify addresses for this user'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|exists:addresses,id',
            'type' => 'required|string|in:billing,shipping'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the address belongs to the user
        $address = Address::where('user_id', $userId)->find($request->address_id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found for this user'
            ], 404);
        }

        // Unset all other default addresses of the same type
        Address::where('user_id', $userId)
            ->where('type', $request->type)
            ->update(['is_default' => false]);

        // Set the specified address as default
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default address updated successfully',
            'data' => $address
        ]);
    }
}
