<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DonationCampaignController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/donations/campaigns",
     *     tags={"Donation Campaigns"},
     *     summary="List all donation campaigns",
     *     @OA\Response(
     *         response=200,
     *         description="List of donation campaigns",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="overview", type="string"),
     *                 @OA\Property(property="deadline", type="string", format="date"),
     *                 @OA\Property(property="fund_needed", type="number", format="float"),
     *                 @OA\Property(property="total_collected", type="number", format="float"),
     *                 @OA\Property(property="price_options", type="array", @OA\Items(type="number")),
     *                 @OA\Property(property="allow_custom_price", type="boolean"),
     *                 @OA\Property(property="status", type="string", enum={"active","completed","cancelled"}),
     *                 @OA\Property(property="category", type="object")
     *             ))
     *         )
     *     )
     * )
     */
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
     * @OA\Post(
     *     path="/api/donations/campaigns",
     *     tags={"Donation Campaigns"},
     *     summary="Create a new donation campaign",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id","name","deadline","fund_needed"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Campaign Name"),
     *             @OA\Property(property="overview", type="string", example="Campaign overview"),
     *             @OA\Property(property="deadline", type="string", format="date", example="2023-12-31"),
     *             @OA\Property(property="fund_needed", type="number", format="float", example=1000.00),
     *             @OA\Property(property="price_options", type="array", @OA\Items(type="number"), example={10,20,50}),
     *             @OA\Property(property="allow_custom_price", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"active","completed","cancelled"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
     * @OA\Get(
     *     path="/api/donations/campaigns/{campaign}",
     *     tags={"Donation Campaigns"},
     *     summary="Get a specific donation campaign",
     *     @OA\Parameter(
     *         name="campaign",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
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
     * @OA\Put(
     *     path="/api/donations/campaigns/{campaign}",
     *     tags={"Donation Campaigns"},
     *     summary="Update a donation campaign",
     *     @OA\Parameter(
     *         name="campaign",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Updated Campaign Name"),
     *             @OA\Property(property="overview", type="string", example="Updated overview"),
     *             @OA\Property(property="deadline", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="fund_needed", type="number", format="float", example=2000.00),
     *             @OA\Property(property="price_options", type="array", @OA\Items(type="number"), example={10,20,50}),
     *             @OA\Property(property="allow_custom_price", type="boolean", example=false),
     *             @OA\Property(property="status", type="string", enum={"active","completed","cancelled"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
     * @OA\Delete(
     *     path="/api/donations/campaigns/{campaign}",
     *     tags={"Donation Campaigns"},
     *     summary="Delete a donation campaign",
     *     @OA\Parameter(
     *         name="campaign",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
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
     * @OA\Get(
     *     path="/api/donations/campaigns/active",
     *     tags={"Donation Campaigns"},
     *     summary="List active donation campaigns",
     *     @OA\Response(
     *         response=200,
     *         description="List of active campaigns",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
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
