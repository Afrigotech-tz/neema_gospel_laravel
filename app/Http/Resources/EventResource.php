<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'start_date' => $this->start_date->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'date_range' => $this->date_range,
            'venue' => $this->venue,
            'location' => $this->location,
            'city' => $this->city,
            'country' => $this->country,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'image_url' => $this->image_url ? Storage::url($this->image_url) : null,
            'capacity' => $this->capacity,
            'attendees_count' => $this->attendees_count,
            'formatted_attendees' => $this->formatted_attendees,
            'is_featured' => $this->is_featured,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'ticket_price' => $this->ticket_price,
            'ticket_url' => $this->ticket_url,
            'tags' => $this->tags ?? [],
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];



    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'live_recording' => 'Live Recording',
            'concert' => 'Concert',
            'service' => 'Service',
            'conference' => 'Conference',
            'workshop' => 'Workshop',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }

}
