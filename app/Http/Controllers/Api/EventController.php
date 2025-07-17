<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of events
     */
    public function index(Request $request)
    {
        $query = Event::query();

        // Filter by type
        if ($request->has('type')) {
            $query->type($request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->featured();
        }

        // Search events
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Date filtering
        if ($request->has('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $events = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Events retrieved successfully'
        ]);
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:live_recording,concert,service,conference,workshop,other',
            'start_date' => 'required|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
            'venue' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'capacity' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'ticket_price' => 'nullable|numeric|min:0',
            'ticket_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'event_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('events', $filename, 'public');
            $data['image_url'] = $path;
        }

        $event = Event::create($data);

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
            'description' => 'nullable|string',
            'type' => 'sometimes|in:live_recording,concert,service,conference,workshop,other',
            'start_date' => 'sometimes|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
            'venue' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'capacity' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
            'ticket_price' => 'nullable|numeric|min:0',
            'ticket_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image_url && Storage::disk('public')->exists($event->image_url)) {
                Storage::disk('public')->delete($event->image_url);
            }

            $file = $request->file('image');
            $filename = 'event_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('events', $filename, 'public');
            $data['image_url'] = $path;
        }

        $event->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ]);
    }

    /**
     * Remove the specified event
     */
    public function destroy(Event $event)
    {
        // Delete associated image
        if ($event->image_url && Storage::disk('public')->exists($event->image_url)) {
            Storage::disk('public')->delete($event->image_url);
        }

        $event->delete();

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
        $events = Event::upcoming()
            ->public()
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Upcoming events retrieved successfully'
        ]);
    }

    /**
     * Get featured events
     */
    public function featured()
    {
        $events = Event::featured()
            ->public()
            ->upcoming()
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Featured events retrieved successfully'
        ]);
    }

    /**
     * Search events
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

        $events = Event::public()
            ->search($request->query)
            ->orderBy('start_date')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'message' => 'Events search results'
        ]);
    }
}
