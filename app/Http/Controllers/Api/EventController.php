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
     * @OA\Get(
     *     path="/api/events",
     *     tags={"Events"},
     *     summary="Get list of events",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by event type",
     *         @OA\Schema(type="string", enum={"concert","service","live_recording","conference","other"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter events from date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter events to date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of events",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     * 
     * list all events
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


    /**
     * @OA\Post(
     *     path="/api/events",
     *     tags={"Events"},
     *     summary="Create a new event",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","type","date","location"},
     *             @OA\Property(property="title", type="string", example="Gospel Concert 2024"),
     *             @OA\Property(property="type", type="string", enum={"concert","service","live_recording","conference","other"}, example="concert"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-12-25"),
     *             @OA\Property(property="location", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="description", type="string", example="Annual gospel concert"),
     *             @OA\Property(property="venue", type="string", example="National Stadium"),
     *             @OA\Property(property="city", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="country", type="string", example="Tanzania"),
     *             @OA\Property(property="capacity", type="integer", example=1000),
     *             @OA\Property(property="ticket_price", type="number", example=50.00),
     *             @OA\Property(property="ticket_url", type="string", format="url", example=""),
     *             @OA\Property(property="is_featured", type="boolean", example=true),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"upcoming","ongoing","completed","cancelled"}, example="upcoming")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
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
     * 
     * create tickets 
     * 
     */
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
     * @OA\Get(
     *     path="/api/events/{event}",
     *     tags={"Events"},
     *     summary="Get event details",
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="Event ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     )
     * )
     * 
     * list all events
     * 
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
     * @OA\Put(
     *     path="/api/events/{event}",
     *     tags={"Events"},
     *     summary="Update event details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="Event ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Gospel Concert"),
     *             @OA\Property(property="type", type="string", enum={"concert","service","live_recording","conference","other"}, example="concert"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-12-26"),
     *             @OA\Property(property="location", type="string", example="Updated Location"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="venue", type="string", example="Updated Venue"),
     *             @OA\Property(property="city", type="string", example="Updated City"),
     *             @OA\Property(property="country", type="string", example="Updated Country"),
     *             @OA\Property(property="capacity", type="integer", example=1500),
     *             @OA\Property(property="ticket_price", type="number", example=60.00),
     *             @OA\Property(property="ticket_url", type="string", format="url", example="updated tickets "),
     *             @OA\Property(property="is_featured", type="boolean", example=false),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"upcoming","ongoing","completed","cancelled"}, example="upcoming")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     * update tickets
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
     * @OA\Delete(
     *     path="/api/events/{id}",
     *     tags={"Events"},
     *     summary="Delete an event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Event ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/events/upcoming",
     *     tags={"Events"},
     *     summary="Get upcoming events",
     *     @OA\Response(
     *         response=200,
     *         description="List of upcoming events",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     * 
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
     * @OA\Get(
     *     path="/api/events/search",
     *     tags={"Events"},
     *     summary="Search events",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query for event title or location",
     *         required=true,
     *         @OA\Schema(type="string", minLength=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     * 
     *  serach event from the list 
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

