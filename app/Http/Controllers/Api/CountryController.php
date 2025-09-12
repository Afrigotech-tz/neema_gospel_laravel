<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/countries",
     *     tags={"Countries"},
     *     summary="Get list of countries",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of countries",
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
     * @OA\Post(
     *     path="/api/countries",
     *     tags={"Countries"},
     *     summary="Create a new country",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code","dial_code"},
     *             @OA\Property(property="name", type="string", example="Tanzania"),
     *             @OA\Property(property="code", type="string", example="TZ"),
     *             @OA\Property(property="dial_code", type="string", example="+255")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Country created successfully",
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
     * @OA\Get(
     *     path="/api/countries/{id}",
     *     tags={"Countries"},
     *     summary="Get country details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Country ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/countries/{id}",
     *     tags={"Countries"},
     *     summary="Update country details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Country ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Tanzania"),
     *             @OA\Property(property="code", type="string", example="TZ"),
     *             @OA\Property(property="dial_code", type="string", example="+255")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/countries/{id}",
     *     tags={"Countries"},
     *     summary="Delete a country",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Country ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete country with associated users"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/countries/search",
     *     tags={"Countries"},
     *     summary="Search countries",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query for country name, code, or dial code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
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
     * @OA\Get(
     *     path="/api/countries/list",
     *     tags={"Countries"},
     *     summary="Get simple list of countries for dropdowns",
     *     @OA\Response(
     *         response=200,
     *         description="Simple list of countries",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="dial_code", type="string")
     *             ))
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/countries/{id}/users",
     *     tags={"Countries"},
     *     summary="Get users from a specific country",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Country ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users from the country",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="country", type="object"),
     *                 @OA\Property(property="users", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="per_page", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found"
     *     )
     * )
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
