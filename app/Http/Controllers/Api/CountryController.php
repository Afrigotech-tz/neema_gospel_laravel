<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of countries
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);
        $countries = Country::withCount('users')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Store a newly created country
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name',
            'code' => 'required|string|max:10|unique:countries,code',
            'dial_code' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $country = Country::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'dial_code' => $request->dial_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Country created successfully',
            'data' => $country
        ], 201);
    }

    /**
     * Display the specified country
     */
    public function show($id)
    {
        $country = Country::withCount('users')->find($id);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $country
        ]);
    }

    /**
     * Update the specified country
     */
    public function update(Request $request, $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:countries,name,' . $id,
            'code' => 'sometimes|required|string|max:10|unique:countries,code,' . $id,
            'dial_code' => 'sometimes|required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['name', 'dial_code']);

        if ($request->has('code')) {
            $updateData['code'] = strtoupper($request->code);
        }

        $country->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Country updated successfully',
            'data' => $country
        ]);
    }

    /**
     * Remove the specified country
     */
    public function destroy($id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        // Check if country has users
        if ($country->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete country with associated users'
            ], 422);
        }

        $country->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully'
        ]);
    }

    /**
     * Search countries
     */
    public function search(Request $request)
    {
        $query = $request->get('query');
        $perPage = $request->get('per_page', 50);

        $countries = Country::withCount('users')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('code', 'LIKE', "%{$query}%")
                  ->orWhere('dial_code', 'LIKE', "%{$query}%");
            })
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get all countries (simple list for dropdowns)
     */
    public function list()
    {
        $countries = Country::select('id', 'name', 'code', 'dial_code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get users by country
     */
    public function users($id, Request $request)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        $perPage = $request->get('per_page', 15);
        $users = $country->users()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $country,
                'users' => $users
            ]
        ]);
    }
}
