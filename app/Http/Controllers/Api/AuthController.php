<?php

namespace App\Http\Controllers\Api;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\Role;
use App\Models\User;
use App\Services\SmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Register a new user with either email or phone number. */

    /**
     * @OA\Post(
     *     path="/api/register",
     *     operationId="registerUser",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","surname","password","country_id","verification_method"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="surname", type="string", example="Doe"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}),
     *             @OA\Property(property="phone_number", type="string", example="+255712345678"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="verification_method", type="string", enum={"email","mobile"}),
     *             @OA\Property(property="role_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="verification_method", type="string", enum={"email","mobile"})
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="SMS service not configured"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'phone_number' => 'required_without:email|nullable|string|unique:users,phone_number',
            'email' => 'required_without:phone_number|nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'country_id' => 'required|exists:countries,id',
            'verification_method' => 'required|in:email,mobile',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine the verification method based on input
        $verification_method = $request->input('verification_method');

        $user = User::create([
            'first_name' => $request->first_name,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country_id' => $request->country_id,
            'verification_method' => $verification_method,
            'status' => User::STATUS_INACTIVE,
        ]);

        // Assign default role if provided, otherwise assign 'user' role
        if ($request->has('role_id') && !empty($request->role_id)) {
            $user->assignRole($request->role_id);
        } else {
            // Assign default 'user' role
            $defaultRole = Role::where('name', 'user')->first();
            if ($defaultRole) {
                $user->assignRole($defaultRole->id);
            }
        }

        // Generate OTP
        $otp = $user->generateOtp();

        // Fallback to synchronous sending if RabbitMQ fails
        // if ($user->verification_method === 'mobile') {
        //     $smsService = new SmService();
        //     if ($smsService->isConfigured()) {
        //         $smsService->sendOtp($user->phone_number, $otp);
        //         $message = 'User registered successfully. Please verify your account using the OTP sent to your phone.';
        //     } else {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'SMS Service is not configured. Cannot send OTP.',
        //         ], 500);
        //     }
        // } else {
        //     Mail::to($user->email)->send(new SendOtpMail($otp));
        //     $message = 'User registered successfully. Please verify your account using the OTP sent to your email.';
        // }

        // return response()->json([
        //     'success' => true,
        //     'message' => $message,
        //     'data' => [
        //         'user' => $user->load('country'),
        //         'verification_method' => $user->verification_method,
        //     ]
        // ], 201);

        //  Use event driven to send notifications

        event(new UserRegistered($user, $otp));

        $message = $user->verification_method === 'mobile'
            ? 'User registered successfully. Please verify your account using the OTP sent to your phone.'
            : 'User registered successfully. Please verify your account using the OTP sent to your email.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user' => $user->load('country'),
                'verification_method' => $user->verification_method,
            ]
        ], 201);
    }

    /** Login user with email or phone number */

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Login using email or phone number",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password"},
     *             @OA\Property(property="login", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account not verified",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
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
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $login = $request->login;

        // Determine if login is email or phone number
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $user = User::where($loginField, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if (!$user->isActive()) {
            return response()->json(['success' => false, 'message' => 'Account not verified. Please verify your account.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Get all permissions from user's roles
        $permissions = $user->roles->flatMap->permissions->pluck('name')->unique()->values();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load(['country', 'roles']),
                'token' => $token,
                'token_type' => 'Bearer',
                'permissions' => $permissions
            ]
        ]);
    }

    /** Verify OTP and activate account */

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     tags={"Authentication"},
     *     summary="Verify OTP",
     *     description="Verify user OTP and activate account",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","otp_code"},
     *             @OA\Property(property="login", type="string", example="john@example.com"),
     *             @OA\Property(property="otp_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account verified successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $login = $request->login;
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $user = User::where($loginField, $login)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->isOtpValid($request->otp_code)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 422);
        }

        $user->activate();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account verified successfully',
            'data' => [
                'user' => $user->load('country'),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /** Resend OTP to user's email or phone */

    /**
     * @OA\Post(
     *     path="/api/resend-otp",
     *     tags={"Authentication"},
     *     summary="Resend OTP",
     *     description="Resend OTP to email or phone",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login"},
     *             @OA\Property(property="login", type="string", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP resent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="SMS service not configured",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $login = $request->login;
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $user = User::where($loginField, $login)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $otp = $user->generateOtp();

        //  synchoronous send notifications
        if ($user->verification_method === 'mobile') {
            $smsService = new SmService();
            if ($smsService->isConfigured()) {
                $smsService->sendOtp($user->phone_number, $otp);
                $message = 'New OTP sent successfully to your phone.';
            } else {
                return response()->json(['success' => false, 'message' => 'SMS Service is not configured.'], 500);
            }
        } else {
            Mail::to($user->email)->send(new SendOtpMail($otp));
            $message = 'New OTP sent successfully to your email.';
        }

        //   DONT DELETE THE CODE

        // Use RabbitMQ for async notification
        // $notificationService = new \App\Services\NotificationPublisherService(new \App\Services\RabbitMQService());
        // $published = $notificationService->publishOtpResendNotification($user, $otp);

        // if ($published) {
        //     $message = 'New OTP sent successfully to your ' . $user->verification_method . '.';
        // } else {
        //     // Fallback to synchronous sending if RabbitMQ fails
        //     if ($user->verification_method === 'mobile') {
        //         $smsService = new SmService();
        //         if ($smsService->isConfigured()) {
        //             $smsService->sendOtp($user->phone_number, $otp);
        //             $message = 'New OTP sent successfully to your phone.';
        //         } else {
        //             return response()->json(['success' => false, 'message' => 'SMS Service is not configured.'], 500);
        //         }
        //     } else {
        //         Mail::to($user->email)->send(new SendOtpMail($otp));
        //         $message = 'New OTP sent successfully to your email.';
        //     }
        // }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /** Logout user */

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

 
}
