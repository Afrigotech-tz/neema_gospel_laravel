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
     * @OA\Post(
     *     path="/api/addresses",
     *     tags={"Addresses"},
     *     summary="Create a new address",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","first_name","last_name","phone","email","address_line_1","city","state","postal_code","country"},
     *             @OA\Property(property="type", type="string", enum={"billing","shipping"}, example="billing"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Main Street"),
     *             @OA\Property(property="address_line_2", type="string", example="Apt 4B"),
     *             @OA\Property(property="city", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="state", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="postal_code", type="string", example="12345"),
     *             @OA\Property(property="country", type="string", example="Tanzania"),
     *             @OA\Property(property="is_default", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Address created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/addresses/{id}",
     *     tags={"Addresses"},
     *     summary="Update an address",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Address ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"billing","shipping"}, example="billing"),
     *             @OA\Property(property="first_name", type="string", example="Updated John"),
     *             @OA\Property(property="last_name", type="string", example="Updated Doe"),
     *             @OA\Property(property="phone", type="string", example="+255712345678"),
     *             @OA\Property(property="email", type="string", format="email", example="updated.john.doe@example.com"),
     *             @OA\Property(property="address_line_1", type="string", example="456 Updated Street"),
     *             @OA\Property(property="address_line_2", type="string", example="Suite 5C"),
     *             @OA\Property(property="city", type="string", example="Updated City"),
     *             @OA\Property(property="state", type="string", example="Updated State"),
     *             @OA\Property(property="postal_code", type="string", example="67890"),
     *             @OA\Property(property="country", type="string", example="Updated Country"),
     *             @OA\Property(property="is_default", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/addresses/{id}",
     *     tags={"Addresses"},
     *     summary="Delete an address",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Address ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete default address"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/addresses",
     *     tags={"Addresses"},
     *     summary="Get all addresses for authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of addresses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/addresses/{id}",
     *     tags={"Addresses"},
     *     summary="Get a specific address",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Address ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/addresses/user/{userId}",
     *     tags={"Addresses"},
     *     summary="Get addresses for a specific user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of addresses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view addresses for this user"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/addresses/user/{userId}/default",
     *     tags={"Addresses"},
     *     summary="Set a default address for a user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_id","type"},
     *             @OA\Property(property="address_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", enum={"billing","shipping"}, example="billing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Default address updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to modify addresses for this user"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found for this user"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
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
