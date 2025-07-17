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
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'date' => $this->date->toISOString(),
            'location' => $this->location,
            'picture' => $this->picture ? Storage::url($this->picture) : null,
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
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }
}
