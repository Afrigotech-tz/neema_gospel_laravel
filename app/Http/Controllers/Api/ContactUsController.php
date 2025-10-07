<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/contact-us",
     *     operationId="getContactUs",
     *     tags={"CMS Contact Us"},
     *     summary="Get contact us information",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="office_hours", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $contactUs = ContactUs::first();
        if (!$contactUs) {
            return response()->json(['success' => false, 'message' => 'Contact us information not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $contactUs]);
    }

    /**
     * @OA\Post(
     *     path="/api/contact-us",
     *     operationId="createContactUs",
     *     tags={"CMS Contact Us"},
     *     summary="Create contact us information",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address", "phone", "email", "office_hours"},
     *             @OA\Property(property="address", type="string", example="123 Main St, City, Country"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@neemagospel.com"),
     *             @OA\Property(property="office_hours", type="string", example="Mon-Fri 9AM-5PM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact us created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="office_hours", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (ContactUs::exists()) {
            return response()->json(['success' => false, 'message' => 'Contact us information already exists. Use update instead.'], 409);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'office_hours' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $contactUs = ContactUs::create($request->all());
        return response()->json(['success' => true, 'data' => $contactUs], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not needed for single record
    }

    /**
     * @OA\Post(
     *     path="/api/contact-us/update",
     *     operationId="updateContactUs",
     *     tags={"CMS Contact Us"},
     *     summary="Update contact us information",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="address", type="string", example="Updated address"),
     *             @OA\Property(property="phone", type="string", example="+0987654321"),
     *             @OA\Property(property="email", type="string", format="email", example="updated@neemagospel.com"),
     *             @OA\Property(property="office_hours", type="string", example="Mon-Sat 8AM-6PM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact us updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="office_hours", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id = null)
    {
        $contactUs = ContactUs::first();
        if (!$contactUs) {
            return response()->json(['success' => false, 'message' => 'Contact us information not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string',
            'email' => 'sometimes|required|email',
            'office_hours' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()->all()], 422);
        }

        $contactUs->update($request->all());
        return response()->json(['success' => true, 'data' => $contactUs]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Not needed for single record
    }
}
