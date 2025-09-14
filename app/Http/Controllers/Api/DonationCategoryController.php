<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DonationCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/donations/categories",
     *     tags={"Donation Categories"},
     *     summary="List all donation categories",
     *     @OA\Response(
     *         response=200,
     *         description="List of donation categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    /**
     * Display a listing of donation categories.
     */
    public function index()
    {
        $categories = DonationCategory::all();
        return response()->json([
            'success' => true,
            'data' => $categories
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/donations/categories",
     *     tags={"Donation Categories"},
     *     summary="Create a new donation category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Education")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Category already exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Step 1: Trim the name
        $name = trim($request->input('name'));

        // Step 2: Check if a category with the same name already exists (case-insensitive)
        $exists = DonationCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Donation category with this name already exists.'
            ], Response::HTTP_CONFLICT);
        }

        // Step 3: Validate
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        // Step 4: Create the category
        $category = DonationCategory::create([
            'name' => $name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Donation category created successfully',
            'data' => $category
        ], Response::HTTP_CREATED);


    }

    /**
     * @OA\Get(
     *     path="/api/donations/categories/{category}",
     *     tags={"Donation Categories"},
     *     summary="Get a specific donation category",
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    /**
     * Display the specified donation category.
     *
     */
    public function show(DonationCategory $category)
    {
        return response()->json([
            'success' => true,
            'data' => $category
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *     path="/api/donations/categories/{category}",
     *     tags={"Donation Categories"},
     *     summary="Update a donation category",
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Education")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    /**
     * Update the specified donation category.
     */
    public function update(Request $request, DonationCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:donation_categories,name,' . $category->id
        ], [
            'name.unique' => 'Donation category with this name already exists.'
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Donation category updated successfully',
            'data' => $category
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/api/donations/categories/{category}",
     *     tags={"Donation Categories"},
     *     summary="Delete a donation category",
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Remove the specified donation category.
     */
    public function destroy(DonationCategory $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Donation category deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/donations/categories/check-name",
     *     tags={"Donation Categories"},
     *     summary="Check if a donation category name exists",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Education")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Check result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="exists", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    /**
     * Check if a donation category name exists.
     */
    public function checkNameExists(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $name = $request->input('name');
        $exists = DonationCategory::where('name', $name)->exists();

        if ($exists) {
            $category = DonationCategory::where('name', $name)->first();
            return response()->json([
                'success' => true,
                'exists' => true,
                'message' => 'Donation category with this name already exists',
                'data' => $category
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => true,
            'exists' => false,
            'message' => 'Donation category name is available'
        ], Response::HTTP_OK);


    }


}
