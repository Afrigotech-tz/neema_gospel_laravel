<?php

namespace App\Http\Controllers\Api;

use App\Events\UserMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\UserMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserMessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user-messages",
     *     operationId="getUserMessages",
     *     tags={"User Messages"},
     *     summary="Get list of user messages",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserMessage"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $messages = UserMessage::orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['success' => true, 'data' => $messages]);
    }

    /**
     * @OA\Post(
     *     path="/api/user-messages",
     *     operationId="createUserMessage",
     *     tags={"User Messages"},
     *     summary="Create a new user message",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "subject", "message"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="subject", type="string", example="Inquiry about services"),
     *             @OA\Property(property="message", type="string", example="I would like to know more about your services.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/UserMessage")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $data = $request->only(['first_name', 'last_name', 'email', 'phone', 'subject', 'message']);

        // Dispatch the event for asynchronous processing
        UserMessageCreated::dispatch($data);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been received and will be processed shortly.',
            'data' => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'subject' => $data['subject']
            ]
        ], 202); // 202 Accepted - request has been received but not yet acted upon
    }

    /**
     * @OA\Get(
     *     path="/api/user-messages/{id}",
     *     operationId="getUserMessage",
     *     tags={"User Messages"},
     *     summary="Get a specific user message",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/UserMessage")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $message = UserMessage::find($id);
        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $message]);
    }

    /**
     * @OA\Put(
     *     path="/api/user-messages/{id}",
     *     operationId="updateUserMessage",
     *     tags={"User Messages"},
     *     summary="Update a user message",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending", "read", "replied", "closed"}, example="read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/UserMessage")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $message = UserMessage::find($id);
        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,read,replied,closed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $message->update($request->only(['status']));
        return response()->json(['success' => true, 'data' => $message]);
    }

    /**
     * @OA\Delete(
     *     path="/api/user-messages/{id}",
     *     operationId="deleteUserMessage",
     *     tags={"User Messages"},
     *     summary="Delete a user message",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $message = UserMessage::find($id);
        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        $message->delete();
        return response()->json(['success' => true, 'message' => 'Message deleted successfully']);
    }
}
