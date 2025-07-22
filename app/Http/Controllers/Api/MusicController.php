<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MusicResource;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MusicController extends Controller
{
    /**
     * Display a listing of the music.
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
     * Store a newly created music.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'release_date' => 'required|date|before_or_equal:today',
            'choir' => 'required|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a,flac|max:50000', // 50MB max
            'description' => 'nullable|string|max:1000',
            'genre' => 'nullable|string|max:100',
        ], [
            'name.required' => 'The music name is required.',
            'name.max' => 'The music name must not exceed 255 characters.',
            'release_date.required' => 'The release date is required.',
            'release_date.date' => 'The release date must be a valid date.',
            'release_date.before_or_equal' => 'The release date must be today or in the past.',
            'choir.required' => 'The choir/artist name is required.',
            'choir.max' => 'The choir/artist name must not exceed 255 characters.',
            'audio_file.required' => 'The audio file is required.',
            'audio_file.file' => 'The uploaded file must be a valid file.',
            'audio_file.mimes' => 'The audio file must be in MP3, WAV, OGG, M4A, or FLAC format.',
            'audio_file.max' => 'The audio file must not exceed 50MB.',
            'picture.image' => 'The picture must be an image file.',
            'picture.mimes' => 'The picture must be in JPEG, PNG, JPG, or GIF format.',
            'picture.max' => 'The picture must not exceed 2MB.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'genre.max' => 'The genre must not exceed 100 characters.',
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

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                $audioFile = $request->file('audio_file');
                $audioPath = $audioFile->store('music/audio', 'public');

                $musicData['audio_file'] = $audioPath;
                $musicData['file_size'] = $audioFile->getSize();
                $musicData['mime_type'] = $audioFile->getMimeType();

                // Get duration (requires getID3 library or similar)
                // For now, we'll skip duration calculation
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
     * Display the specified music.
     */
    public function show(Music $music)
    {
        return response()->json([
            'success' => true,
            'data' => new MusicResource($music)
        ]);
    }

    /**
     * Update the specified music.
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
     * Remove the specified music.
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
