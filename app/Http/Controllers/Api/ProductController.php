<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by search term
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by active products only
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Sort products
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants.attributeValues.attribute'])->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Get product categories.
     */
    public function categories()
    {
        $categories = ProductCategory::where('is_active', true)
            ->with(['children' => function($query) {
                $query->where('is_active', true);
            }])
            ->whereNull('parent_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get products by category.
     */
    public function productsByCategory($categoryId)
    {
        $category = ProductCategory::find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $products = Product::with(['variants'])
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);

        
    }
}
