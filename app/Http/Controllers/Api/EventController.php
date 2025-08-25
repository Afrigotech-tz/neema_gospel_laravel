<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Catch_;

class EventController extends Controller
{
    /**
     * Display a listing of events
     *
     */

    public function index(Request $request)
    {
        $query = Event::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Date filtering
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Sorting
          $sortBy = $request->get('sort_by', 'date','desc');
        // $sortOrder = $request->get('sort_order', 'asc');
        // $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);

        $cacheKey = 'events_' . md5(json_encode($request->all()));

        // Cache for 10 minutes
        $events = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Events retrieved successfully'
        ]);


    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:concert,service,live_recording,conference,other',
            'date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'picture' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'status' => 'string|in:upcoming,ongoing,completed,cancelled',
            'ticket_price' => 'nullable|numeric|min:0',
            'ticket_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only use validated data
        $data = $validator->validated();

        // Handle picture upload if provided as file
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $filename = 'event_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('events', $filename, 'public');
            $data['picture'] = $path;
        }

        $event = Event::create($data);

        //Cache::tags('events')->flush();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201);
    }


    /**
     * Display the specified event
     */
    public function show(Event $event)
    {
        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Event retrieved successfully'
        ]);
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:concert,service,live_recording,conference,other',
            'date' => 'sometimes|date|after:now',
            'location' => 'sometimes|string|max:255',
            'picture' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'status' => 'string|in:upcoming,ongoing,completed,cancelled',
            'ticket_price' => 'nullable|numeric|min:0',
            'ticket_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle picture upload if provided
        if ($request->hasFile('picture')) {
            // Delete old picture if exists
            if ($event->picture && Storage::disk('public')->exists($event->picture)) {
                Storage::disk('public')->delete($event->picture);
            }

            $file = $request->file('picture');
            $filename = 'event_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('events', $filename, 'public');
            $data['picture'] = $path;
        }

        $event->update($data);
        //Cache::tags('events')->flush();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ]);
    }



    /**
     * Remove the specified event
     */

    public function destroy($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => "Event not found"
            ], 404);
        }

        // Delete associated picture
        if ($event->picture && Storage::disk('public')->exists($event->picture)) {
            Storage::disk('public')->delete($event->picture);
        }

        $event->delete();

        //Cache::tags('events')->flush();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }




    /**
     * Get upcoming events
     */
    public function upcoming()
    {
        $events = Event::where('date', '>', now())
            ->orderBy('date')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Upcoming events retrieved successfully'
        ]);
    }

    /**
     * Search events
     *
     */

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $events = Event::where('title', 'like', '%' . $request->query . '%')
            ->orWhere('location', 'like', '%' . $request->query . '%')
            ->orderBy('date')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Events search results'
        ]);
    }
}
