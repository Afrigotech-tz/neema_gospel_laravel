<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/news",
     *     tags={"News"},
     *     summary="Get list of news",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="featured",
     *         in="query",
     *         description="Filter by featured status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by title or content",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of news",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = News::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by title or content
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sort by published date
        $query->orderBy('published_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $news = $query->paginate($perPage);

        return NewsResource::collection($news);
    }

    /**
     * @OA\Post(
     *     path="/api/news",
     *     tags={"News"},
     *     summary="Create new news article",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title","content","published_at","category","type"},
     *                 @OA\Property(property="title", type="string", example="New Gospel Album Released"),
     *                 @OA\Property(property="slug", type="string", example="new-gospel-album-released"),
     *                 @OA\Property(property="content", type="string", example="Full article content here..."),
     *                 @OA\Property(property="excerpt", type="string", example="Brief summary of the article"),
     *                 @OA\Property(property="published_at", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="category", type="string", example="Music"),
     *                 @OA\Property(property="type", type="string", example="article"),
     *                 @OA\Property(property="author", type="string", example="John Doe"),
     *                 @OA\Property(property="location", type="string", example="Dar es Salaam"),
     *                 @OA\Property(property="is_featured", type="boolean", example=true),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="featured_image", type="string", format="binary", description="Featured image (JPEG, PNG, JPG, GIF, max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="News created successfully",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create news"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:news,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'published_at' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'duration' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'is_featured' => 'boolean|default:false',
            'is_published' => 'boolean|default:true',
            'author' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $newsData = $request->only([
                'title', 'content', 'excerpt', 'published_at', 'category',
                'type', 'duration', 'location', 'is_featured', 'is_published',
                'author', 'tags'
            ]);

            // Generate slug if not provided
            if (empty($newsData['slug'])) {
                $newsData['slug'] = Str::slug($newsData['title']);
            }

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $imagePath = $request->file('featured_image')->store('news/images', 'public');
                $newsData['featured_image'] = $imagePath;
            }

            $news = News::create($newsData);

            return response()->json([
                'success' => true,
                'message' => 'News created successfully',
                'data' => new NewsResource($news)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/news/{news}",
     *     tags={"News"},
     *     summary="Get news details",
     *     @OA\Parameter(
     *         name="news",
     *         in="path",
     *         description="News ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="News details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="News not found"
     *     )
     * )
     */
    public function show(News $news)
    {
        return response()->json([
            'success' => true,
            'data' => new NewsResource($news)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/news/{news}",
     *     tags={"News"},
     *     summary="Update news article",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="news",
     *         in="path",
     *         description="News ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Updated Gospel Album Released"),
     *                 @OA\Property(property="slug", type="string", example="updated-gospel-album-released"),
     *                 @OA\Property(property="content", type="string", example="Updated article content..."),
     *                 @OA\Property(property="excerpt", type="string", example="Updated summary"),
     *                 @OA\Property(property="published_at", type="string", format="date", example="2024-01-16"),
     *                 @OA\Property(property="category", type="string", example="Updated Category"),
     *                 @OA\Property(property="type", type="string", example="article"),
     *                 @OA\Property(property="author", type="string", example="Jane Doe"),
     *                 @OA\Property(property="location", type="string", example="Updated Location"),
     *                 @OA\Property(property="is_featured", type="boolean", example=false),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="featured_image", type="string", format="binary", description="New featured image")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="News updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="News not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update news"
     *     )
     * )
     */
    public function update(Request $request, News $news)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|unique:news,slug,' . $news->id,
            'content' => 'sometimes|required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'published_at' => 'sometimes|required|date|before_or_equal:today',
            'category' => 'sometimes|required|string|max:50',
            'type' => 'sometimes|required|string|max:50',
            'duration' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'is_featured' => 'boolean|default:false',
            'is_published' => 'boolean|default:true',
            'author' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $newsData = $request->only([
                'title', 'content', 'excerpt', 'published_at', 'category',
                'type', 'duration', 'location', 'is_featured', 'is_published',
                'author', 'tags'
            ]);

            // Generate slug if provided and different
            if ($request->has('slug') && !empty($newsData['slug'])) {
                $newsData['slug'] = Str::slug($newsData['slug']);
            } elseif ($request->has('title') && $newsData['title'] !== $news->title) {
                $newsData['slug'] = Str::slug($newsData['title']);
            }

            // Handle featured image update
            if ($request->hasFile('featured_image')) {
                // Delete old image if exists
                if ($news->featured_image) {
                    Storage::disk('public')->delete($news->featured_image);
                }

                $imagePath = $request->file('featured_image')->store('news/images', 'public');
                $newsData['featured_image'] = $imagePath;
            }

            $news->update($newsData);

            return response()->json([
                'success' => true,
                'message' => 'News updated successfully',
                'data' => new NewsResource($news)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/news/{news}",
     *     tags={"News"},
     *     summary="Delete news article",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="news",
     *         in="path",
     *         description="News ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="News deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="News not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete news"
     *     )
     * )
     */
    public function destroy(News $news)
    {
        try {
            // Delete associated image if exists
            if ($news->featured_image) {
                Storage::disk('public')->delete($news->featured_image);
            }

            $news->delete();

            return response()->json([
                'success' => true,
                'message' => 'News deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/news/featured",
     *     tags={"News"},
     *     summary="Get featured news",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of featured news",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function featured()
    {
        $news = News::where('is_featured', true)
                    ->where('is_published', true)
                    ->orderBy('published_at', 'desc')
                    ->paginate(10);

        return NewsResource::collection($news);
    }

    /**
     * @OA\Get(
     *     path="/api/news/recent",
     *     tags={"News"},
     *     summary="Get recent news",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of recent news",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function recent()
    {
        $news = News::where('is_published', true)
                    ->where('published_at', '>=', now()->subDays(30))
                    ->orderBy('published_at', 'desc')
                    ->paginate(10);

        return NewsResource::collection($news);
    }
}
