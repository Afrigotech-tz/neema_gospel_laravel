<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_attributes';

    protected $fillable = [
        'name',
        'type',
        'is_required'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }
}
