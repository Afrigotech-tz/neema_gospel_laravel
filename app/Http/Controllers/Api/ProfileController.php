<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            // Create empty profile if doesn't exist
            $profile = UserProfile::create(['user_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->load('country'),
                'profile' => $profile
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'location_public' => 'nullable|boolean',
            'profile_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get or create profile
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $data = $request->except(['profile_picture']);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($profile->profile_picture && Storage::disk('public')->exists($profile->profile_picture)) {
                Storage::disk('public')->delete($profile->profile_picture);
            }

            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $data['profile_picture'] = $path;
        }

        $profile->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->load('country'),
                'profile' => $profile->fresh()
            ]
        ]);
    }

    /**
     * Update profile picture only
     */
    public function updateProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        // Delete old profile picture if exists
        if ($profile->profile_picture && Storage::disk('public')->exists($profile->profile_picture)) {
            Storage::disk('public')->delete($profile->profile_picture);
        }

        // Store new profile picture with optimized naming
        $file = $request->file('profile_picture');
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile-pictures', $filename, 'public');

        // Optimize the image for better performance
        $this->optimizeImage($path);

        $profile->update(['profile_picture' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'data' => [
                'profile_picture_url' => $profile->profile_picture_url
            ]
        ]);
    }

    /**
     * Update location coordinates
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'location_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $profile->update($request->only([
            'latitude',
            'longitude',
            'address',
            'city',
            'state_province',
            'postal_code',
            'location_public'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'location' => [
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                    'address' => $profile->full_address,
                    'location_public' => $profile->location_public
                ]
            ]
        ]);
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->profile_picture) {
            return response()->json([
                'success' => false,
                'message' => 'No profile picture to delete'
            ], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($profile->profile_picture)) {
            Storage::disk('public')->delete($profile->profile_picture);
        }

        $profile->update(['profile_picture' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture deleted successfully'
        ]);
    }

    /**
     * Optimize uploaded image for better performance
     */
    private function optimizeImage($path)
    {
        try {
            // Use Intervention Image to optimize the image
            $image = Image::make(Storage::disk('public')->path($path));

            // Resize if too large (max 800x800 while maintaining aspect ratio)
            $image->resize(800, 800, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Compress and save with 85% quality for good balance
            $image->save(null, 85);

        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::warning('Image optimization failed: ' . $e->getMessage());
        }
    }
}
