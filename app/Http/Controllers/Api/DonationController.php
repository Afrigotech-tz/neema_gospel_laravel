<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\DonationCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DonationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/donations",
     *     tags={"Donations"},
     *     summary="Get list of donations",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of donations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $donations = Donation::with(['user', 'campaign'])->latest()->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $donations
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/donations",
     *     tags={"Donations"},
     *     summary="Create a new donation",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"campaign_id","donor_name","donor_email","amount","transaction_reference"},
     *             @OA\Property(property="campaign_id", type="integer", example=1),
     *             @OA\Property(property="donor_name", type="string", example="John Doe"),
     *             @OA\Property(property="donor_email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="donor_phone", type="string", example="+255712345678"),
     *             @OA\Property(property="amount", type="number", example=100.00),
     *             @OA\Property(property="currency", type="string", example="TZS"),
     *             @OA\Property(property="payment_method", type="string", example="M-Pesa"),
     *             @OA\Property(property="transaction_reference", type="string", example="TXN123456789"),
     *             @OA\Property(property="message", type="string", example="God bless you")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Donation created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:donation_campaigns,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'string|max:3|default:TZS',
            'payment_method' => 'nullable|string|max:50',
            'transaction_reference' => 'required|string|unique:donations',
            'message' => 'nullable|string|max:1000',
        ]);

        $donation = Donation::create($validated);

        // Update campaign total collected
        $campaign = DonationCampaign::find($validated['campaign_id']);
        if ($campaign) {
            $campaign->increment('total_collected', $validated['amount']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Donation created successfully',
            'data' => $donation->load(['user', 'campaign'])
        ], Response::HTTP_CREATED);

    }

    /**
     * @OA\Get(
     *     path="/api/donations/{donation}",
     *     tags={"Donations"},
     *     summary="Get donation details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="donation",
     *         in="path",
     *         description="Donation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation not found"
     *     )
     * )
     */
    public function show(Donation $donation)
    {
        $donation->load(['user', 'campaign']);
        return response()->json([
            'success' => true,
            'data' => $donation
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *     path="/api/donations/{donation}",
     *     tags={"Donations"},
     *     summary="Update donation status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="donation",
     *         in="path",
     *         description="Donation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","completed","failed","refunded"}, example="completed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function update(Request $request, Donation $donation)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $oldAmount = $donation->amount;
        $donation->update($validated);

        // Update campaign total if status changes
        if ($validated['status'] === 'completed' && $donation->wasChanged('status')) {
            $campaign = $donation->campaign;
            $campaign->increment('total_collected', $donation->amount);
        } elseif ($validated['status'] !== 'completed' && $donation->wasChanged('status')) {
            $campaign = $donation->campaign;
            $campaign->decrement('total_collected', $oldAmount);
        }

        return response()->json([
            'success' => true,
            'message' => 'Donation updated successfully',
            'data' => $donation->load(['user', 'campaign'])
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/donations/campaign/{campaign}",
     *     tags={"Donations"},
     *     summary="Get donations by campaign",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="campaign",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of donations for the campaign",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found"
     *     )
     * )
     */
    public function byCampaign(DonationCampaign $campaign)
    {
        $donations = $campaign->donations()->with('user')->get();
        return response()->json([
            'success' => true,
            'data' => $donations
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/donations/statistics",
     *     tags={"Donations"},
     *     summary="Get donation statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Donation statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_donations", type="number", example=15000.00),
     *                 @OA\Property(property="total_donors", type="integer", example=150),
     *                 @OA\Property(property="total_active_campaigns", type="integer", example=5),
     *                 @OA\Property(property="today_donations", type="number", example=500.00),
     *                 @OA\Property(property="monthly_donations", type="number", example=2500.00)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        $totalDonations = Donation::where('status', 'completed')->sum('amount');
        $totalDonors = Donation::where('status', 'completed')->distinct('donor_email')->count();
        $totalCampaigns = DonationCampaign::where('status', 'active')->count();
        $todayDonations = Donation::where('status', 'completed')->whereDate('created_at', today())->sum('amount');
        $monthlyDonations = Donation::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_donations' => $totalDonations,
                'total_donors' => $totalDonors,
                'total_active_campaigns' => $totalCampaigns,
                'today_donations' => $todayDonations,
                'monthly_donations' => $monthlyDonations
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/donations/user/{user}",
     *     tags={"Donations"},
     *     summary="Get donations by user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user donations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function byUser($user)
    {
        $donations = Donation::with(['campaign'])
            ->where('user_id', $user)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $donations
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/api/donations/{donation}",
     *     tags={"Donations"},
     *     summary="Delete a donation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="donation",
     *         in="path",
     *         description="Donation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Donation deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Donation not found"
     *     )
     * )
     */
    public function destroy(Donation $donation)
    {
        // Decrement campaign total if donation was completed
        if ($donation->status === 'completed' && $donation->campaign) {
            $donation->campaign->decrement('total_collected', $donation->amount);
        }

        $donation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Donation deleted successfully'
        ], Response::HTTP_OK);
    }
}
