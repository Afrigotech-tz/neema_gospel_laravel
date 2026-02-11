<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;




class BlogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/blogs",
     *     operationId="getBlogs",
     *     tags={"CMS Blogs"},
     *     summary="Get list of blogs",
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
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="location", type="string"),
     *                     @OA\Property(property="is_active", type="boolean"),
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
        $blogs = Blog::where('is_active', true)->orderBy('date', 'desc')->get();
        return response()->json(['success' => true, 'data' => $blogs]);
    }

    /**
     * @OA\Post(
     *     path="/api/blogs",
     *     operationId="createBlog",
     *     tags={"CMS Blogs"},
     *     summary="Create a new blog",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description", "date", "location"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file"),
     *                 @OA\Property(property="title", type="string", example="Our Latest Event"),
     *                 @OA\Property(property="description", type="string", example="Details about the event"),
     *                 @OA\Property(property="date", type="string", format="date", example="2023-12-25"),
     *                 @OA\Property(property="location", type="string", example="Church Hall"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Blog created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
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
            'description' => 'required|string',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['title', 'description', 'date', 'location', 'is_active']);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'public');
            $data['image'] = $imagePath;
        }

        $blog = Blog::create($data);
        return response()->json(['success' => true, 'data' => $blog], 201);


    }
    

    /**
     * @OA\Get(
     *     path="/api/blogs/{id}",
     *     operationId="getBlog",
     *     tags={"CMS Blogs"},
     *     summary="Get a specific blog",
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
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $blog]);
    }

    /**
     * @OA\Put(
     *     path="/api/blogs/{id}",
     *     operationId="updateBlog",
     *     tags={"CMS Blogs"},
     *     summary="Update a blog",
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
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="date", type="string", format="date", example="2023-12-26"),
     *                 @OA\Property(property="location", type="string", example="Updated Location"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Blog updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {

         

        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'is_active' => ['sometimes', 'in:true,false,1,0', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['title', 'description', 'date', 'location', 'is_active']);

        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $imagePath = $request->file('image')->store('blogs', 'public');
            $data['image'] = $imagePath;
        }

        $blog->update($data);
        $blog->touch(); // Force update timestamps
        return response()->json(['success' => true, 'data' => $blog]);

    }

    
    
    /**
     * @OA\Delete(
     *     path="/api/blogs/{id}",
     *     operationId="deleteBlog",
     *     tags={"CMS Blogs"},
     *     summary="Delete a blog",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Blog deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Blog deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }

        // Delete image file
        if ($blog->image && Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->delete();
        return response()->json(['success' => true, 'message' => 'Blog deleted successfully']);
    }
}
