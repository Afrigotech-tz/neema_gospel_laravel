<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlider extends Model
{
    protected $fillable = [
        'image',
        'title',
        'head',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Override the image attribute when returning JSON
     */
    public function getImageAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }


}



