<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'product_attribute_values';

    protected $fillable = [
        'attribute_id',
        'value',
        'price_adjustment',
        'stock_quantity',
        'sku'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];


    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'attribute_value_product_variant', 'attribute_value_id', 'product_variant_id')
            ->withPivot('value');
    }

}
