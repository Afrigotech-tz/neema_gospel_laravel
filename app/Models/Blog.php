<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Blog extends Model
{
    protected $fillable = [
        'image',
        'title',
        'description',
        'date',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

}
