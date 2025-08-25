<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'type',
        'date',
        'start_date',
        'end_date',
        'venue',
        'location',
        'city',
        'country',
        'latitude',
        'longitude',
        'image_url',
        'picture',
        'capacity',
        'attendees_count',
        'is_featured',
        'is_public',
        'status',
        'ticket_price',
        'ticket_url',
        'tags',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ticket_price' => 'decimal:2',
        'tags' => 'array',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Get the full URL for the event picture.
     *
     * @return string|null
     */
    public function getPictureUrlAttribute()
    {
        if ($this->picture) {
            return Storage::url($this->picture);
        }
        return null;
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())->where('status', 'upcoming');
    }

    /**
     * Scope a query to only include ongoing events.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->where('status', 'ongoing');
    }

    /**
     * Scope a query to only include featured events.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include public events.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query by event type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to search events.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('venue', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    /**
     * Get the event's formatted date range.
     */
    public function getDateRangeAttribute()
    {
        if ($this->end_date) {
            return $this->start_date->format('M d, Y H:i') . ' - ' . $this->end_date->format('M d, Y H:i');
        }
        return $this->start_date->format('M d, Y H:i');
    }

    /**
     * Get the event's formatted attendees count.
     */
    public function getFormattedAttendeesAttribute()
    {
        if ($this->attendees_count >= 1000) {
            return round($this->attendees_count / 1000, 1) . 'K+';
        }
        return $this->attendees_count . ' Going';
    }
}
