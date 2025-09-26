<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="HomeSlider",
 *     type="object",
 *     title="HomeSlider",
 *     description="Home Slider model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="image", type="string", nullable=true, example="sliders/image.jpg"),
 *     @OA\Property(property="title", type="string", example="Slider Title"),
 *     @OA\Property(property="head", type="string", example="Slider Head"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Slider description"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
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
