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
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Get authenticated user's profile",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="profile", type="object")
     *             )
     *         )
     *     )
     * )
     */
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
     * @OA\Put(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Update authenticated user's profile",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="profile_picture", type="string", format="binary"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="state_province", type="string", example="NY"),
     *                 @OA\Property(property="postal_code", type="string", example="10001"),
     *                 @OA\Property(property="bio", type="string", example="About me"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="occupation", type="string", example="Developer"),
     *                 @OA\Property(property="location_public", type="boolean", example=true),
     *                 @OA\Property(property="profile_public", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
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
     * @OA\Post(
     *     path="/api/profile/picture",
     *     tags={"Profile"},
     *     summary="Update profile picture",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"profile_picture"},
     *                 @OA\Property(property="profile_picture", type="string", format="binary", description="Image file (jpeg, png, jpg, gif, webp, max 5MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile picture updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="profile_picture_url", type="string")
     *             )
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
     * @OA\Put(
     *     path="/api/profile/location",
     *     tags={"Profile"},
     *     summary="Update profile location",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state_province", type="string", example="NY"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="location_public", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="location", type="object",
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="location_public", type="boolean")
     *                 )
     *             )
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
     * @OA\Delete(
     *     path="/api/profile/picture",
     *     tags={"Profile"},
     *     summary="Delete profile picture",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile picture deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No profile picture to delete",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
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
