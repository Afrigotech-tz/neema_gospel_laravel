<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeSliderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/home-sliders",
     *     operationId="getHomeSliders",
     *     tags={"Home Sliders"},
     *     summary="Get list of home sliders",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="image", type="string", nullable=true),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="head", type="string"),
     *                     @OA\Property(property="description", type="string", nullable=true),
     *                     @OA\Property(property="is_active", type="boolean"),
     *                     @OA\Property(property="sort_order", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $sliders = HomeSlider::orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $sliders]);
    }

    /**
     * @OA\Post(
     *     path="/api/home-sliders",
     *     operationId="createHomeSlider",
     *     tags={"Home Sliders"},
     *     summary="Create a new home slider",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "head"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file"),
     *                 @OA\Property(property="title", type="string", example="Welcome to Neema Gospel"),
     *                 @OA\Property(property="head", type="string", example="Empowering Faith"),
     *                 @OA\Property(property="description", type="string", example="Join us in our mission"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="sort_order", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Slider created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="head", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="sort_order", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'required|string|max:255',
            'head' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['title', 'head', 'description', 'is_active', 'sort_order']);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('home_sliders', 'public');
            $data['image'] = $imagePath;
        }

        $slider = HomeSlider::create($data);
        return response()->json(['success' => true, 'data' => $slider], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/home-sliders/{id}",
     *     operationId="getHomeSlider",
     *     tags={"Home Sliders"},
     *     summary="Get a specific home slider",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="head", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="sort_order", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slider not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $slider = HomeSlider::find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $slider]);
    }

    /**
     * @OA\Post(
     *     path="/api/home-sliders/{id}",
     *     operationId="updateHomeSlider",
     *     tags={"Home Sliders"},
     *     summary="Update a home slider",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file"),
     *                 @OA\Property(property="title", type="string", example="Updated Title"),
     *                 @OA\Property(property="head", type="string", example="Updated Head"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="sort_order", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slider updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="head", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="sort_order", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $slider = HomeSlider::find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'sometimes|required|string|max:255',
            'head' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['title', 'head', 'description', 'is_active', 'sort_order']);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }
            $imagePath = $request->file('image')->store('home_sliders', 'public');
            $data['image'] = $imagePath;
        }

        $slider->update($data);
        return response()->json(['success' => true, 'data' => $slider]);
    }

    /**
     * @OA\Delete(
     *     path="/api/home-sliders/{id}",
     *     operationId="deleteHomeSlider",
     *     tags={"Home Sliders"},
     *     summary="Delete a home slider",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slider deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Slider deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slider not found"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $slider = HomeSlider::find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider not found'], 404);
        }

        // Delete image file
        if ($slider->image && Storage::disk('public')->exists($slider->image)) {
            Storage::disk('public')->delete($slider->image);
        }

        $slider->delete();
        return response()->json(['success' => true, 'message' => 'Slider deleted successfully']);
    }
}
