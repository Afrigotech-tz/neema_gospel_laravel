<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ContactUs",
 *     type="object",
 *     title="ContactUs",
 *     description="Contact Us model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="address", type="string", example="123 Main St, City, Country"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@example.com"),
 *     @OA\Property(property="office_hours", type="string", example="Mon-Fri 9AM-5PM"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ContactUs extends Model
{
    protected $fillable = [
        'address',
        'phone',
        'email',
        'office_hours',
    ];


}

