<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'country_id' => 'required|exists:countries,id',
            'verification_method' => 'nullable|in:email,mobile',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country_id' => $request->country_id,
            'verification_method' => $request->verification_method ?? 'email',
            'status' => User::STATUS_INACTIVE,
        ]);

        // Generate and send OTP
        $otp = $user->generateOtp();

        // In a real application, you would send the OTP via email or SMS
        // For now, we'll return it in the response for testing purposes
        // TODO: Implement email/SMS sending functionality

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Please verify your account using the OTP sent to your email.',
            'data' => [
                'user' => $user->load('country'),
                'otp' => $otp, // Remove this in production
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
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->login;
        $password = $request->password;

        // Determine if login is email or phone number
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        // Find user by email or phone number
        $user = User::where($loginField, $login)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if account is verified
        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account not verified. Please verify your account using the OTP sent to your email.'
            ], 403);
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
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Send OTP to user's email or phone
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Generate new OTP
        $otp = $user->generateOtp();

        // TODO: Implement email sending functionality
        // For now, return OTP in response for testing
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your email',
            'data' => [
                'otp' => $otp, // Remove in production
                'expires_at' => $user->otp_expires_at,
            ]
        ]);
    }

    /**
     * Verify OTP and activate account
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check if OTP is valid
        if (!$user->isOtpValid($request->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 422);
        }

        // Activate user account
        $user->activate();

        // Generate token for the user
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
     * Resend OTP to user's email
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Generate new OTP
        $otp = $user->generateOtp();

        // TODO: Implement email sending functionality
        return response()->json([
            'success' => true,
            'message' => 'New OTP sent successfully to your email',
            'data' => [
                'otp' => $otp, // Remove in production
                'expires_at' => $user->otp_expires_at,
            ]
        ]);
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
