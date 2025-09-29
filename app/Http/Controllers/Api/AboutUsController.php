<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AboutUsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/about-us",
     *     operationId="getAboutUs",
     *     tags={"About Us"},
     *     summary="Get about us content",
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
     *                 @OA\Property(property="our_story", type="string"),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="mission", type="string"),
     *                 @OA\Property(property="vision", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $aboutUs = AboutUs::first();
        if (!$aboutUs) {
            return response()->json(['success' => false, 'message' => 'About us content not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $aboutUs]);
    }

    /**
     * @OA\Post(
     *     path="/api/about-us",
     *     operationId="createAboutUs",
     *     tags={"About Us"},
     *     summary="Create about us content",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"our_story", "mission", "vision"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file"),
     *                 @OA\Property(property="our_story", type="string", example="Our story content"),
     *                 @OA\Property(property="mission", type="string", example="Our mission statement"),
     *                 @OA\Property(property="vision", type="string", example="Our vision statement")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="About us created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="our_story", type="string"),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="mission", type="string"),
     *                 @OA\Property(property="vision", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (AboutUs::exists()) {
            return response()->json(['success' => false, 'message' => 'About us content already exists. Use update instead.'], 409);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'our_story' => 'required|string',
            'mission' => 'required|string',
            'vision' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['our_story', 'mission', 'vision']);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('about_us', 'public');
            $data['image'] = $imagePath;
        }

        $aboutUs = AboutUs::create($data);
        return response()->json(['success' => true, 'data' => $aboutUs], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not needed for single record
    }

    /**
     * @OA\Post(
     *     path="/api/about-us/update",
     *     operationId="updateAboutUs",
     *     tags={"About Us"},
     *     summary="Update about us content",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file"),
     *                 @OA\Property(property="our_story", type="string", example="Updated story"),
     *                 @OA\Property(property="mission", type="string", example="Updated mission"),
     *                 @OA\Property(property="vision", type="string", example="Updated vision")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="About us updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="our_story", type="string"),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="mission", type="string"),
     *                 @OA\Property(property="vision", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id = null)
    {
        $aboutUs = AboutUs::first();
        if (!$aboutUs) {
            return response()->json(['success' => false, 'message' => 'About us content not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'our_story' => 'sometimes|required|string',
            'mission' => 'sometimes|required|string',
            'vision' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['our_story', 'mission', 'vision']);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($aboutUs->image && Storage::disk('public')->exists($aboutUs->image)) {
                Storage::disk('public')->delete($aboutUs->image);
            }
            $imagePath = $request->file('image')->store('about_us', 'public');
            $data['image'] = $imagePath;
        }

        $aboutUs->update($data);
        return response()->json(['success' => true, 'data' => $aboutUs]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Not needed for single record
    }


}

