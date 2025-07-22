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
     * Display a listing of the news.
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
     * Store a newly created news.
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
     * Display the specified news.
     */
    public function show(News $news)
    {
        return response()->json([
            'success' => true,
            'data' => new NewsResource($news)
        ]);
    }

    /**
     * Update the specified news.
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
     * Remove the specified news.
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
     * Get featured news
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
     * Get recent news
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
