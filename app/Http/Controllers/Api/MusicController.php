<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MusicResource;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use getID3;

class MusicController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/music",
     *     tags={"Music"},
     *     summary="Get list of music",
     *     @OA\Parameter(
     *         name="choir",
     *         in="query",
     *         description="Filter by choir/artist",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="genre",
     *         in="query",
     *         description="Filter by genre",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by music name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of music",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Music::query();

        // Filter by choir
        if ($request->has('choir')) {
            $query->where('choir', 'like', '%' . $request->choir . '%');
        }

        // Filter by genre
        if ($request->has('genre')) {
            $query->where('genre', $request->genre);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort by release date
        $query->orderBy('release_date', 'desc');

        $music = $query->paginate($request->get('per_page', 15));

        return MusicResource::collection($music);
    }

    /**
     * @OA\Post(
     *     path="/api/music",
     *     tags={"Music"},
     *     summary="Upload new music",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","release_date","choir"},
     *                 @OA\Property(property="name", type="string", example="Amazing Grace"),
     *                 @OA\Property(property="release_date", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="choir", type="string", example="Neema Gospel Choir"),
     *                 @OA\Property(property="description", type="string", example="A beautiful gospel hymn"),
     *                 @OA\Property(property="genre", type="string", example="Gospel"),
     *                 @OA\Property(property="picture", type="string", format="binary", description="Music cover image (JPEG, PNG, JPG, GIF, max 2MB)"),
     *                 @OA\Property(property="audio_file", type="string", format="binary", description="Audio file (MP3, WAV, OGG, M4A, FLAC, max 50MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Music uploaded successfully",
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to upload music"
     *     )
     * )
     */
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'release_date' => 'required|date|before_or_equal:today',
    //         'choir' => 'required|string|max:255',
    //         'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,flac|max:50000', // 50MB max
    //         'description' => 'nullable|string|max:1000',
    //         'genre' => 'nullable|string|max:100',
    //     ],
    //     [
    //         'name.required' => 'The music name is required.',
    //         'name.max' => 'The music name must not exceed 255 characters.',
    //         'release_date.required' => 'The release date is required.',
    //         'release_date.date' => 'The release date must be a valid date.',
    //         'release_date.before_or_equal' => 'The release date must be today or in the past.',
    //         'choir.required' => 'The choir/artist name is required.',
    //         'choir.max' => 'The choir/artist name must not exceed 255 characters.',
    //         'audio_file.required' => 'The audio file is required.',
    //         'audio_file.file' => 'The uploaded file must be a valid file.',
    //         'audio_file.mimes' => 'The audio file must be in MP3, WAV, OGG, M4A, or FLAC format.',
    //         'audio_file.max' => 'The audio file must not exceed 50MB.',
    //         'picture.image' => 'The picture must be an image file.',
    //         'picture.mimes' => 'The picture must be in JPEG, PNG, JPG, or GIF format.',
    //         'picture.max' => 'The picture must not exceed 2MB.',
    //         'description.max' => 'The description must not exceed 1000 characters.',
    //         'genre.max' => 'The genre must not exceed 100 characters.',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation errors',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }
    //     try {
    //         $musicData = $request->only(['name', 'release_date', 'choir', 'description', 'genre']);
    //         // Handle picture upload
    //         if ($request->hasFile('picture')) {
    //             $picturePath = $request->file('picture')->store('music/pictures', 'public');
    //             $musicData['picture'] = $picturePath;
    //         }
    //         // Handle audio file upload
    //         if ($request->hasFile('audio_file')) {
    //             $audioFile = $request->file('audio_file');
    //             $audioPath = $audioFile->store('music/audio', 'public');
    //             $musicData['audio_file'] = $audioPath;
    //             $musicData['file_size'] = $audioFile->getSize();
    //             $musicData['mime_type'] = $audioFile->getMimeType();
    //             // Get duration (requires getID3 library or similar)
    //             // For now, we'll skip duration calculation
    //         }
    //         $music = Music::create($musicData);
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Music uploaded successfully',
    //             'data' => new MusicResource($music)
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to upload music',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'release_date' => 'required|date|before_or_equal:today',
            'choir' => 'required|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,flac|max:50000',
            'description' => 'nullable|string|max:1000',
            'genre' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $musicData = $request->only(['name', 'release_date', 'choir', 'description', 'genre']);

            // Handle picture upload
            if ($request->hasFile('picture')) {
                $picturePath = $request->file('picture')->store('music/pictures', 'public');
                $musicData['picture'] = $picturePath;
            }

            // Handle audio upload
            if ($request->hasFile('audio_file')) {
                $audioFile = $request->file('audio_file');
                $audioPath = $audioFile->store('music/audio', 'public');

                $musicData['audio_file'] = $audioPath;
                $musicData['file_size'] = $audioFile->getSize();
                $musicData['mime_type'] = $audioFile->getMimeType();

                // Calculate duration using getID3
                $getID3 = new getID3;
                $fileInfo = $getID3->analyze(storage_path('app/public/' . $audioPath));
                
                $durationSeconds = isset($fileInfo['playtime_seconds'])
                    ? round($fileInfo['playtime_seconds'])
                    : 0;

                $musicData['duration'] = $durationSeconds;

            }

            $music = Music::create($musicData);

            return response()->json([
                'success' => true,
                'message' => 'Music uploaded successfully',
                'data' => new MusicResource($music)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload music',
                'error' => $e->getMessage()
            ], 500);
        }
  
    }

    /**
     * @OA\Get(
     *     path="/api/music/{music}",
     *     tags={"Music"},
     *     summary="Get music details",
     *     @OA\Parameter(
     *         name="music",
     *         in="path",
     *         description="Music ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Music details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Music not found"
     *     )
     * )
     */
    public function show(Music $music)
    {
        return response()->json([
            'success' => true,
            'data' => new MusicResource($music)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/music/{music}",
     *     tags={"Music"},
     *     summary="Update music details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="music",
     *         in="path",
     *         description="Music ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Amazing Grace"),
     *                 @OA\Property(property="release_date", type="string", format="date", example="2024-01-16"),
     *                 @OA\Property(property="choir", type="string", example="Updated Choir"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="genre", type="string", example="Updated Genre"),
     *                 @OA\Property(property="picture", type="string", format="binary", description="New cover image"),
     *                 @OA\Property(property="audio_file", type="string", format="binary", description="New audio file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Music updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Music not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update music"
     *     )
     * )
     */
    public function update(Request $request, Music $music)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'release_date' => 'sometimes|required|date',
            'choir' => 'sometimes|required|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:50000',
            'description' => 'nullable|string',
            'genre' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $musicData = $request->only(['name', 'release_date', 'choir', 'description', 'genre']);

            // Handle picture upload
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($music->picture) {
                    Storage::disk('public')->delete($music->picture);
                }

                $picturePath = $request->file('picture')->store('music/pictures', 'public');
                $musicData['picture'] = $picturePath;
            }

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file if exists
                if ($music->audio_file) {
                    Storage::disk('public')->delete($music->audio_file);
                }

                $audioFile = $request->file('audio_file');
                $audioPath = $audioFile->store('music/audio', 'public');

                $musicData['audio_file'] = $audioPath;
                $musicData['file_size'] = $audioFile->getSize();
                $musicData['mime_type'] = $audioFile->getMimeType();
            }

            $music->update($musicData);

            return response()->json([
                'success' => true,
                'message' => 'Music updated successfully',
                'data' => new MusicResource($music)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update music',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/music/{music}",
     *     tags={"Music"},
     *     summary="Delete music",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="music",
     *         in="path",
     *         description="Music ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Music deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Music not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete music"
     *     )
     * )
     */
    public function destroy(Music $music)
    {
        try {
            // Delete associated files
            if ($music->picture) {
                Storage::disk('public')->delete($music->picture);
            }

            if ($music->audio_file) {
                Storage::disk('public')->delete($music->audio_file);
            }

            $music->delete();

            return response()->json([
                'success' => true,
                'message' => 'Music deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete music',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

