<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DonationCategoryController extends Controller
{
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
