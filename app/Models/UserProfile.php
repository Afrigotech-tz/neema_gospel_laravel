<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'profile_picture',
        'address',
        'city',
        'state_province',
        'postal_code',
        'bio',
        'date_of_birth',
        'occupation',
        'location_public',
        'profile_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_public' => 'boolean',
        'profile_public' => 'boolean',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state_province,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if the profile has location coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get the profile picture URL.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->profile_picture, FILTER_VALIDATE_URL)) {
            return $this->profile_picture;
        }

        // Otherwise, prepend the storage URL
        return asset('storage/' . $this->profile_picture);
    }
}
