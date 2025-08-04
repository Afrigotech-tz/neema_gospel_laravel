<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductManagementController extends Controller
{
    public function storeProduct(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'tags' => 'nullable|array'
        ]);

        $product = Product::create($request->all());

        return response()->json(['message' => 'Product created', 'data' => $product->load('category')]);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'tags' => 'nullable|array'
        ]);

        $product->update($request->all());

        return response()->json(['message' => 'Product updated', 'data' => $product->load('category')]);
    }

    public function deleteProduct(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $category = ProductCategory::create($request->only('name', 'description', 'parent_id', 'is_active'));

        return response()->json(['message' => 'Category created', 'data' => $category]);
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $category->update($request->only('name', 'description', 'parent_id', 'is_active'));

        return response()->json(['message' => 'Category updated', 'data' => $category]);
    }

    public function deleteCategory(ProductCategory $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }

    public function storeVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'attribute_values' => 'nullable|array',
            'attribute_values.*' => 'exists:product_attribute_values,id'
        ]);

        DB::transaction(function () use ($request, &$variant) {
            $variant = ProductVariant::create($request->only('product_id', 'sku', 'price', 'stock', 'color', 'size'));
            if ($request->has('attribute_values')) {
                $variant->attributeValues()->syncWithoutDetaching($request->attribute_values);
            }
        });

        return response()->json(['message' => 'Variant created', 'data' => $variant->load('attributeValues.attribute')]);
    }

    public function updateVariant(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'sku' => 'required|string|unique:product_variants,sku,' . $variant->id,
            'price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'color' => 'nullable|string',
            'size' => 'nullable|string',
            'attribute_values' => 'nullable|array',
            'attribute_values.*' => 'exists:product_attribute_values,id'
        ]);

        DB::transaction(function () use ($request, $variant) {
            $variant->update($request->only('sku', 'price', 'stock', 'color', 'size'));
            if ($request->has('attribute_values')) {
                $variant->attributeValues()->sync($request->attribute_values);
            }
        });

        return response()->json(['message' => 'Variant updated', 'data' => $variant->load('attributeValues.attribute')]);
    }

    public function deleteVariant(ProductVariant $variant)
    {
        $variant->delete();
        return response()->json(['message' => 'Variant deleted']);
    }

    public function storeAttribute(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $attribute = ProductAttribute::create($request->only('name'));

        return response()->json(['message' => 'Attribute created', 'data' => $attribute]);
    }

    public function storeAttributeValue(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:255'
        ]);

        $value = ProductAttributeValue::create($request->only('attribute_id', 'value'));

        return response()->json(['message' => 'Attribute value created', 'data' => $value]);
    }
    
}
