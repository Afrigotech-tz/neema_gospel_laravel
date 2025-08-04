<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
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
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'occupation' => 'nullable|string|max:100',
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

        // Optimize the image with high quality settings
        $this->optimizeImage($path, [
            'quality' => 98,
            'resize' => false, // Don't resize profile pictures to maintain quality
            'format' => 'webp', // Use WebP for better compression without quality loss
            'backup_original' => false // Do not store original image
        ]);

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
     * Optimize uploaded image with configurable quality settings
     */
    private function optimizeImage($path, $options = [])
    {
        $defaults = [
            'quality' => 95, // Increased from 85% to 95% for better quality
            'max_width' => 1200, // Increased from 800 to allow larger images
            'max_height' => 1200, // Increased from 800 to allow larger images
            'resize' => false, // Make resizing optional to preserve original dimensions
            'format' => null, // Allow format conversion
            'backup_original' => true // Keep original high-quality version
        ];

        $options = array_merge($defaults, $options);

        try {
            // Create image manager
            $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

            $fullPath = Storage::disk('public')->path($path);
            $image = $manager->read($fullPath);

            // Backup original if requested
            if ($options['backup_original']) {
                $originalPath = str_replace('.jpg', '_original.jpg', $fullPath);
                $originalPath = str_replace('.jpeg', '_original.jpeg', $originalPath);
                $originalPath = str_replace('.png', '_original.png', $originalPath);
                $originalPath = str_replace('.gif', '_original.gif', $originalPath);
                $originalPath = str_replace('.webp', '_original.webp', $originalPath);

                if (!file_exists($originalPath)) {
                    copy($fullPath, $originalPath);
                }
            }

            // Only resize if explicitly requested and image is larger than limits
            if ($options['resize']) {
                $width = $image->width();
                $height = $image->height();

                if ($width > $options['max_width'] || $height > $options['max_height']) {
                    $image->scaleDown($options['max_width'], $options['max_height']);
                }
            }

            // Handle format conversion for better compression
            if ($options['format'] === 'webp' && function_exists('imagewebp')) {
                $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $fullPath);
                $image->save($webpPath, $options['quality']);

                // Update the path to use WebP version
                if (file_exists($webpPath)) {
                    unlink($fullPath);
                    $path = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $path);
                }
            } else {
                // Save with high quality settings
                $image->save($fullPath, $options['quality']);
            }

        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::warning('Image optimization failed: ' . $e->getMessage());
        }


    }
}
