<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DonationCampaignController extends Controller
{
    /**
     * Display a listing of donation campaigns.
     */
    public function index()
    {
        $campaigns = DonationCampaign::with('category')->get();
        return response()->json([
            'success' => true,
            'data' => $campaigns
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created donation campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:donation_categories,id',
            'name' => 'required|string|max:255',
            'overview' => 'nullable|string',
            'deadline' => 'required|date',
            'fund_needed' => 'required|numeric|min:0',
            'price_options' => 'nullable|array',
            'price_options.*' => 'numeric|min:0',
            'allow_custom_price' => 'boolean',
            'status' => 'in:active,completed,cancelled'
        ]);

        $campaign = DonationCampaign::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Donation campaign created successfully',
            'data' => $campaign
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified donation campaign.
     */
    public function show(DonationCampaign $campaign)
    {
        $campaign->load('category');
        return response()->json([
            'success' => true,
            'data' => $campaign
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified donation campaign.
     */
    public function update(Request $request, DonationCampaign $campaign)
    {
        $validated = $request->validate([
            'category_id' => 'exists:donation_categories,id',
            'name' => 'string|max:255',
            'overview' => 'nullable|string',
            'deadline' => 'date',
            'fund_needed' => 'numeric|min:0',
            'price_options' => 'nullable|array',
            'price_options.*' => 'numeric|min:0',
            'allow_custom_price' => 'boolean',
            'status' => 'in:active,completed,cancelled'
        ]);

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Donation campaign updated successfully',
            'data' => $campaign
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified donation campaign.
     */
    public function destroy(DonationCampaign $campaign)
    {
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Donation campaign deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Get active campaigns only.
     */
    public function active()
    {
        $campaigns = DonationCampaign::with('category')
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ], Response::HTTP_OK);
    }
}
