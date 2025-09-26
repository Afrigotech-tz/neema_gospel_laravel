<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Blog",
 *     type="object",
 *     title="Blog",
 *     description="Blog model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="image", type="string", nullable=true, example="blogs/image.jpg"),
 *     @OA\Property(property="title", type="string", example="Blog Title"),
 *     @OA\Property(property="description", type="string", example="Blog description"),
 *     @OA\Property(property="date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="location", type="string", nullable=true, example="Location"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
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
