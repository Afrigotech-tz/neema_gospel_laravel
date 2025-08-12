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

class ProductManagementController extends Controller
{
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
                            $existingImages = array_filter($existingImages, function($img) use ($imageToRemove) {
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
     * Create product variant.
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
            'stock_quantity' => 'sometimes|required|integer|min:0',
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
