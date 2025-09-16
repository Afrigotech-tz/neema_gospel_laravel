<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Product Management",
 *     description="APIs for managing products, categories, variants, and attributes"
 * )
 */
class ProductManagementController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/products",
     *     tags={"Product Management"},
     *     summary="Create a new product",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","category_id","base_price","sku","stock_quantity"},
     *                 @OA\Property(property="name", type="string", example="Sample Product"),
     *                 @OA\Property(property="description", type="string", example="Product description"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="base_price", type="number", format="float", example=99.99),
     *                 @OA\Property(property="sku", type="string", example="PROD-001"),
     *                 @OA\Property(property="stock_quantity", type="integer", example=100),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="weight", type="number", format="float", example=1.5),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:product_categories,id',
            'base_price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'weight' => 'nullable|numeric|min:0',

        ]);

        DB::beginTransaction();
        try {

            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'base_price' => $request->base_price,
                'sku' => $request->sku,
                'stock_quantity' => $request->stock_quantity,
                'is_active' => $request->boolean('is_active', true),
                'weight' => $request->weight,

            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $images[] = $path;
                }
                $product->update(['images' => $images]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load('category')
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/{id}",
     *     tags={"Product Management"},
     *     summary="Update an existing product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Product"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="base_price", type="number", format="float", example=149.99),
     *                 @OA\Property(property="sku", type="string", example="PROD-001-UPD"),
     *                 @OA\Property(property="stock_quantity", type="integer", example=150),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="weight", type="number", format="float", example=2.0),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *                 @OA\Property(property="images_action", type="string", enum={"replace","add","remove","keep"}, example="add"),
     *                 @OA\Property(property="remove_images", type="array", @OA\Items(type="string"), example={"products/image1.jpg"}),
     *                 @OA\Property(property="meta_title", type="string", example="Meta Title"),
     *                 @OA\Property(property="meta_description", type="string", example="Meta Description"),
     *                 @OA\Property(property="dimensions", type="array", @OA\Items(type="string"), example={"length":"10","width":"5"}),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"tag1","tag2"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Update an existing product.
     */

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([

            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'base_price' => 'sometimes|required|numeric|min:0',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'images_action' => 'nullable|in:replace,add,remove,keep',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string'
        ]);

        DB::beginTransaction();
        try {
            $updateData = $request->except(['images']);

            if ($request->has('dimensions')) {
                $updateData['dimensions'] = json_encode($request->dimensions);
            }

            if ($request->has('tags')) {
                $updateData['tags'] = json_encode($request->tags);
            }

            $product->update($updateData);

            // Handle image updates
            if ($request->has('images_action')) {
                $action = $request->input('images_action');

                switch ($action) {
                    case 'replace':
                        // Delete old images
                        if ($product->images) {
                            $oldImages = is_array($product->images) ? $product->images : json_decode($product->images, true);
                            foreach ($oldImages as $oldImage) {
                                Storage::disk('public')->delete($oldImage);
                            }
                        }

                        $images = [];
                        if ($request->hasFile('images')) {
                            foreach ($request->file('images') as $image) {
                                $path = $image->store('products', 'public');
                                $images[] = $path;
                            }
                        }
                        $product->update(['images' => $images]);
                        break;

                    case 'add':
                        // Add new images to existing ones
                        $existingImages = $product->images ? (is_array($product->images) ? $product->images : json_decode($product->images, true)) : [];

                        if ($request->hasFile('images')) {
                            foreach ($request->file('images') as $image) {
                                $path = $image->store('products', 'public');
                                $existingImages[] = $path;
                            }
                        }
                        $product->update(['images' => $existingImages]);
                        break;

                    case 'remove':
                        // Remove specific images
                        $imagesToRemove = $request->input('remove_images', []);
                        $existingImages = $product->images ? (is_array($product->images) ? $product->images : json_decode($product->images, true)) : [];

                        foreach ($imagesToRemove as $imageToRemove) {
                            Storage::disk('public')->delete($imageToRemove);
                            $existingImages = array_filter($existingImages, function ($img) use ($imageToRemove) {
                                return $img !== $imageToRemove;
                            });
                        }

                        $product->update(['images' => array_values($existingImages)]);
                        break;

                    case 'keep':
                    default:
                        // Keep existing images, no changes
                        break;
                }
            } elseif ($request->hasFile('images')) {
                // Backward compatibility: if no images_action specified but images provided
                $existingImages = $product->images ? (is_array($product->images) ? $product->images : json_decode($product->images, true)) : [];

                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $existingImages[] = $path;
                }

                $product->update(['images' => $existingImages]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load('category')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/{id}",
     *     tags={"Product Management"},
     *     summary="Delete a product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Delete a product.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            // Delete product images
            if ($product->images) {
                $images = json_decode($product->images, true);
                foreach ($images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products/categories",
     *     tags={"Product Management"},
     *     summary="Create a product category",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="description", type="string", example="Electronic products"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="sort_order", type="integer", example=0)
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
     * Create product category.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $category = ProductCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->sort_order ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products/categories",
     *     tags={"Product Management"},
     *     summary="Get all product categories",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getCategory(Request $request)
    {
        $query = ProductCategory::query();
        $query->orderBy('sort_order', 'asc');

        $categories = $query->get();
        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/categories/{id}",
     *     tags={"Product Management"},
     *     summary="Update product category",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Electronics"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="sort_order", type="integer", example=1)
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
     *         response=404,
     *         description="Category not found",
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
    /**
     * Update product category.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:product_categories,name,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/categories/{id}",
     *     tags={"Product Management"},
     *     summary="Delete product category",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
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
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete category with products",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Delete product category.
     */
    public function destroyCategory($id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with products'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products/variants",
     *     tags={"Product Management"},
     *     summary="Get all product variants",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Variants retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    /**
     * Get product variants.
     */
    public function getVariant(Request $request)
    {
        $query = ProductVariant::query();
        $query->orderBy('id', 'asc');

        $variants = $query->get();
        return response()->json([
            'success' => true,
            'message' => 'Variants retrieved successfully',
            'data' => $variants
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products/variants",
     *     tags={"Product Management"},
     *     summary="Create a product variant",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","sku","price","stock","attribute_values"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="sku", type="string", example="PROD-001-S"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="attribute_values", type="array", @OA\Items(type="integer"), example={1,2})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Variant created successfully",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function storeVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'attribute_values' => 'required|array',
            'attribute_values.*' => 'exists:product_attribute_values,id'
        ]);

        DB::beginTransaction();
        try {
            $variant = ProductVariant::create([
                'product_id' => $request->product_id,
                'sku' => $request->sku,
                'price' => $request->price,
                'stock' => $request->stock,
                'is_active' => $request->boolean('is_active', true)
            ]);

            // Attach attribute values
            $variant->attributeValues()->sync($request->attribute_values);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product variant created successfully',
                'data' => $variant->load('attributeValues.attribute')
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create variant',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/variants/{id}",
     *     tags={"Product Management"},
     *     summary="Update product variant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sku", type="string", example="PROD-001-M"),
     *             @OA\Property(property="price", type="number", format="float", example=109.99),
     *             @OA\Property(property="stock", type="integer", example=75),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="attribute_values", type="array", @OA\Items(type="integer"), example={1,3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Variant not found",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Update product variant.
     */
    public function updateVariant(Request $request, $id)
    {
        $variant = ProductVariant::find($id);

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'sku' => 'sometimes|required|string|unique:product_variants,sku,' . $id,
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'boolean',
            'attribute_values' => 'sometimes|required|array',
            'attribute_values.*' => 'exists:product_attribute_values,id'
        ]);

        DB::beginTransaction();
        try {
            $variant->update($request->except(['attribute_values']));

            if ($request->has('attribute_values')) {
                $variant->attributeValues()->sync($request->attribute_values);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Variant updated successfully',
                'data' => $variant->load('attributeValues.attribute')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variant',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/variants/{id}",
     *     tags={"Product Management"},
     *     summary="Delete product variant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Variant not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Delete product variant.
     */
    public function destroyVariant($id)
    {
        $variant = ProductVariant::find($id);

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variant deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products/attributes",
     *     tags={"Product Management"},
     *     summary="Create a product attribute",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type"},
     *             @OA\Property(property="name", type="string", example="Color"),
     *             @OA\Property(property="type", type="string", enum={"text","number","color","select"}, example="select"),
     *             @OA\Property(property="is_required", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attribute created successfully",
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
     * Create product attribute.
     */
    public function storeAttribute(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_attributes,name',
            'type' => 'required|in:text,number,color,select',
            'is_required' => 'boolean'
        ]);

        $attribute = ProductAttribute::create([
            'name' => $request->name,
            'type' => $request->type,
            'is_required' => $request->boolean('is_required', false)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute created successfully',
            'data' => $attribute
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products/attribute-values",
     *     tags={"Product Management"},
     *     summary="Create a product attribute value",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"attribute_id","value"},
     *             @OA\Property(property="attribute_id", type="integer", example=1),
     *             @OA\Property(property="value", type="string", example="Red"),
     *             @OA\Property(property="price_adjustment", type="number", format="float", example=0.00),
     *             @OA\Property(property="stock_quantity", type="integer", example=100),
     *             @OA\Property(property="sku", type="string", example="RED-001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attribute value created successfully",
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
     * Create product attribute value.
     */
    public function storeAttributeValue(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:255',
            'price_adjustment' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:255'
        ]);

        $attributeValue = ProductAttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'value' => $request->value,
            'price_adjustment' => $request->price_adjustment ?? 0,
            'stock_quantity' => $request->stock_quantity ?? 0,
            'sku' => $request->sku
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attribute value created successfully',
            'data' => $attributeValue
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/attribute-values/{id}",
     *     tags={"Product Management"},
     *     summary="Update a product attribute value",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="attribute_id", type="integer", example=1),
     *             @OA\Property(property="value", type="string", example="Blue"),
     *             @OA\Property(property="price_adjustment", type="number", format="float", example=5.00),
     *             @OA\Property(property="stock_quantity", type="integer", example=150),
     *             @OA\Property(property="sku", type="string", example="BLUE-001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attribute value updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attribute value not found",
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
    /**
     * Update product attribute value.
     */
    public function updateAttributeValue(Request $request, $id)
    {
        $attributeValue = ProductAttributeValue::find($id);

        if (!$attributeValue) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute value not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'attribute_id' => 'sometimes|required|exists:product_attributes,id',
            'value' => 'sometimes|required|string|max:255',
            'price_adjustment' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:255'
        ]);

        $attributeValue->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Attribute value updated successfully',
            'data' => $attributeValue
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/attribute-values/{id}",
     *     tags={"Product Management"},
     *     summary="Delete a product attribute value",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attribute value deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete attribute value in use",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attribute value not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Delete product attribute value.
     */
    public function destroyAttributeValue($id)
    {
        $attributeValue = ProductAttributeValue::find($id);

        if (!$attributeValue) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute value not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if attribute value is being used by any variants
        if ($attributeValue->variants()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete attribute value that is being used by product variants'
            ], Response::HTTP_BAD_REQUEST);
        }

        $attributeValue->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attribute value deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products/attribute-values",
     *     tags={"Product Management"},
     *     summary="Get all product attribute values",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="attribute_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Filter by attribute ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attribute values retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    /**
     * Get all attribute values.
     */
    public function getAttributeValues(Request $request)
    {
        $query = ProductAttributeValue::with('attribute');

        if ($request->has('attribute_id')) {
            $query->where('attribute_id', $request->attribute_id);
        }

        $attributeValues = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Attribute values retrieved successfully',
            'data' => $attributeValues
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/products",
     *     tags={"Product Management"},
     *     summary="Get all products (admin view)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Page number for pagination"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    /**
     * Get all products (admin view)
     */
    public function getProducts(Request $request)
    {
        $products = Product::with(['category', 'variants'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ]);
    }

    
}
