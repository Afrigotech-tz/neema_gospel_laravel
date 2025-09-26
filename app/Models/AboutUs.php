<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="AboutUs",
 *     type="object",
 *     title="AboutUs",
 *     description="About Us model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="our_story", type="string", example="Our story content"),
 *     @OA\Property(property="image", type="string", nullable=true, example="about_us/image.jpg"),
 *     @OA\Property(property="mission", type="string", example="Our mission statement"),
 *     @OA\Property(property="vision", type="string", example="Our vision statement"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AboutUs extends Model
{
    protected $fillable = [
        'our_story',
        'image',
        'mission',
        'vision',
    ];


}

