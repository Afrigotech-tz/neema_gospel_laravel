<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sku',
        'base_price',
        'weight',
        'image_url',
        'images',
        'is_active',
        'stock_quantity',
        'category_id'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'images' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Override the image attribute when returning JSON
     */
    // public function getImageAttribute($value): ?string
    // {
    //     return $value ? asset('storage/' . $value) : null;
    // }


    /**
 * Get full path for the primary image_url
 */
public function getImageUrlAttribute($value): ?string
{
    if ($value) {
        return asset('storage/' . $value);
    }

    $images = $this->images; 

    if (is_array($images) && count($images) > 0) {
        return $images[0];
    }

    return null;

}

/**
 * Get full paths for the array of gallery images
 */
public function getImagesAttribute($value): array
{
    // Decode the JSON from DB (already handled by $casts)
    $images = is_array($value) ? $value : json_decode($value, true) ?? [];

    return array_map(function ($image) {
        return asset('storage/' . $image);
    }, $images);

}

}
