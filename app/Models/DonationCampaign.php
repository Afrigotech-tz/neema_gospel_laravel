<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationCampaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'overview',
        'deadline',
        'fund_needed',
        'total_collected',
        'price_options',
        'allow_custom_price',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'date',
        'fund_needed' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'price_options' => 'array',
        'allow_custom_price' => 'boolean',
    ];

    /**
     * Get the category that owns the campaign.
     */
    public function category()
    {
        return $this->belongsTo(DonationCategory::class);
    }

    /**
     * Get the donations for the campaign.
     */
    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Scope a query to only include active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->fund_needed <= 0) {
            return 0;
        }

        return min(100, round(($this->total_collected / $this->fund_needed) * 100, 2));
    }

    /**
     * Get the remaining amount needed.
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->fund_needed - $this->total_collected);
    }

    /**
     * Check if the campaign is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the campaign is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }
}
