<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $url;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $senderId;
    protected string $deliveryReportUrl;

    public function __construct()
    {
        $this->url = config('sms.kilakona.url');
        $this->apiKey = config('sms.kilakona.api_key');
        $this->apiSecret = config('sms.kilakona.api_secret');
        $this->senderId = config('sms.kilakona.sender_id');
        $this->deliveryReportUrl = config('sms.kilakona.delivery_report_url');
    }

    /**
     * Send OTP SMS to a phone number
     *
     * @param string $phoneNumber
     * @param string $otp
     * @return array
     *
     */

    public function sendOtp(string $phoneNumber, string $otp): array
    {
        $message = "Your NEEMA GOSPEL verification code is: {$otp}. This code expires in 10 minutes. Do not share this code with anyone.";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send SMS using Kilakona API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    // public function sendSms(string $phoneNumber, string $message): array
    // {
    //     // Validate phone number format
    //     $phoneNumber = $this->formatPhoneNumber($phoneNumber);

    //     if (!$phoneNumber) {
    //         return [
    //             'success' => false,
    //             'message' => 'Invalid phone number format',
    //             'response' => null
    //         ];
    //     }

    //     $data = [
    //         'senderId' => $this->senderId,
    //         'messageType' => 'text',
    //         'message' => $message,
    //         'contacts' => $phoneNumber,
    //         'deliveryReportUrl' => $this->deliveryReportUrl
    //     ];

    //     try {

    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'api_key' => $this->apiKey,
    //             'api_secret' => $this->apiSecret,
    //         ])->withOptions([
    //             'verify' => false
    //         ])->post($this->url, $data);


    //         if ($response->successful()) {
    //             $responseData = $response->json();

    //             Log::info('SMS sent successfully', [
    //                 'phone' => $phoneNumber,
    //                 'response' => $responseData
    //             ]);

    //             return [
    //                 'success' => true,
    //                 'message' => 'SMS sent successfully',
    //                 'response' => $responseData
    //             ];
    //         }

    //         Log::error('SMS sending failed', [
    //             'phone' => $phoneNumber,
    //             'status' => $response->status(),
    //             'response' => $response->body()
    //         ]);

    //         return [
    //             'success' => false,
    //             'message' => 'Failed to send SMS',
    //             'response' => $response->json() ?? $response->body()
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error('SMS sending exception', [
    //             'phone' => $phoneNumber,
    //             'error' => $e->getMessage()
    //         ]);

    //         return [
    //             'success' => false,
    //             'message' => 'Exception occurred while sending SMS',
    //             'response' => $e->getMessage()
    //         ];
    //     }
    // }


    public function sendSms(string $phoneNumber, string $message): array
    {
        // Validate phone number format
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        if (!$phoneNumber) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format',
                'response' => null
            ];
        }

        $data = [
            'senderId' => $this->senderId,
            'messageType' => 'text',
            'message' => $message,
            'contacts' => $phoneNumber,
            'deliveryReportUrl' => $this->deliveryReportUrl
        ];

        // 🔍 Debug log: What will be sent
        Log::debug('Preparing to send SMS', [
            'url' => $this->url,
            'headers' => [
                'api_key' => $this->apiKey ? '***SET***' : 'MISSING',
                'api_secret' => $this->apiSecret ? '***SET***' : 'MISSING',
            ],
            'data' => $data
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ])->withOptions([
                'verify' => false // 🔧 Only use in development!
            ])->post($this->url, $data);

            // ✅ Success
            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $responseData
                ];
            }

            // ❌ Failed response
            Log::error('SMS sending failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS',
                'response' => $response->json() ?? $response->body()
            ];
        } catch (\Exception $e) {
            // ❗ Exception occurred
            Log::error('SMS sending exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred while sending SMS',
                'response' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to international format
     *
     * @param string $phoneNumber
     * @return string|null
     */
    private function formatPhoneNumber(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle different formats
        if (strlen($phoneNumber) === 9 && substr($phoneNumber, 0, 1) !== '0') {
            // Format: 7XXXXXXXX
            return '255' . $phoneNumber;
        } elseif (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) === '0') {
            // Format: 07XXXXXXXX
            return '255' . substr($phoneNumber, 1);
        } elseif (strlen($phoneNumber) === 12 && substr($phoneNumber, 0, 3) === '255') {
            // Format: 2557XXXXXXXX
            return $phoneNumber;
        }

        return null;
    }

    /**
     * Check if SMS service is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret) && !empty($this->senderId);
    }
}
