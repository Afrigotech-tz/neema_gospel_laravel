<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationCampaignResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new DonationCategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'overview' => $this->overview,
            'deadline' => $this->deadline->toDateString(),
            'fund_needed' => $this->fund_needed,
            'total_collected' => $this->total_collected,
            'price_options' => $this->price_options,
            'allow_custom_price' => $this->allow_custom_price,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'remaining_amount' => $this->remaining_amount,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
