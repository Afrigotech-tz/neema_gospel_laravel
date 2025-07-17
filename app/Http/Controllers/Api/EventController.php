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
        $sortBy = $request->get('sort_by', 'date');
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
            'type' => 'required|in:concert,service,live_recording,conference,other',
            'date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'picture' => 'nullable|string|max:500',
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
            $file = $request->file('picture');
            $filename = 'event_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('events', $filename, 'public');
            $data['picture'] = $path;
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
            'type' => 'sometimes|in:concert,service,live_recording,conference,other',
            'date' => 'sometimes|date|after:now',
            'location' => 'sometimes|string|max:255',
            'picture' => 'nullable|string|max:500',
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
        // Delete associated picture
        if ($event->picture && Storage::disk('public')->exists($event->picture)) {
            Storage::disk('public')->delete($event->picture);
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
