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


}
