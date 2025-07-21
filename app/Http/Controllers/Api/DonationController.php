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
     * Display a listing of donations.
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
     * Store a newly created donation.
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
     * Display the specified donation.
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
     * Update the specified donation.
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
     * Get donations by campaign.
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
     * Get donation statistics.
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
     * Get donations by user.
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
     * Remove the specified donation.
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
