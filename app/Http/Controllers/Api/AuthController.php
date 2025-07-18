<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail; // <-- Import the Mailable
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail; // <-- Import Mail facade
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user with either email or phone number.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            // Require at least one of phone_number or email
            'phone_number' => 'required_without:email|nullable|string|unique:users,phone_number',
            'email' => 'required_without:phone_number|nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine the verification method based on input
        //$verification_method = $request->filled('email') ? 'email' : 'mobile';
        $verification_method = 'email';

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

        // Generate and send OTP
        $otp = $user->generateOtp();
        $message = '';

        // Send OTP based on the verification method used for registration
        if ($user->verification_method === 'mobile') {
            $smsService = new SmsService();
            if ($smsService->isConfigured()) {
                 $smsService->sendOtp($user->phone_number, $otp);
                 $message = 'User registered successfully. Please verify your account using the OTP sent to your phone.';
            } else {
                // Handle case where SMS service is intended but not configured
                // For now, we prevent account creation if SMS fails.
                 return response()->json([
                    'success' => false,
                    'message' => 'SMS Service is not configured. Cannot send OTP.',
                 ], 500);
            }
        } else { // 'email'
            Mail::to($user->email)->send(new SendOtpMail($otp));
            $message = 'User registered successfully. Please verify your account using the OTP sent to your email.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user' => $user->load('country'),
                'verification_method' => $user->verification_method,
            ]
        ], 201);
    }

    /**
     * Login user with email or phone number
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // Can be email or phone number
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

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('country'),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Verify OTP and activate account
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

    /**
     * Resend OTP to user's email or phone
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
        $message = '';

        if ($user->verification_method === 'mobile') {
            $smsService = new SmsService();
             if ($smsService->isConfigured()) {
                $smsService->sendOtp($user->phone_number, $otp);
                $message = 'New OTP sent successfully to your phone.';
             } else {
                 return response()->json(['success' => false, 'message' => 'SMS Service is not configured.'], 500);
             }
        } else { // 'email'
            Mail::to($user->email)->send(new SendOtpMail($otp));
            $message = 'New OTP sent successfully to your email.';
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('country')
        ]);
    }
}
