<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Services\SmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationConsumerService
{
    protected $smsService;

    public function __construct(SmService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function processEmailNotification($channel, $message)
    {
        try {
            $data = json_decode($message->body, true);

            if (!$data || !isset($data['email'], $data['otp'])) {
                Log::error('Invalid email notification message', ['message' => $message->body]);
                $channel->basic_ack($message->get('delivery_tag'));
                return;
            }

            Log::info('Processing email notification', [
                'email' => $data['email'],
                'user_id' => $data['user_id'] ?? 'unknown'
            ]);

            Mail::to($data['email'])->send(new SendOtpMail($data['otp']));

            Log::info('Email notification sent successfully', [
                'email' => $data['email'],
                'user_id' => $data['user_id'] ?? 'unknown'
            ]);

            // Acknowledge the message to remove it from queue
            $channel->basic_ack($message->get('delivery_tag'));
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'error' => $e->getMessage(),
                'message' => $message->body
            ]);
            // Reject the message and requeue it
            $channel->basic_nack($message->get('delivery_tag'), false, true);
        }
    }

    public function processSmsNotification($channel, $message)
    {
        try {
            $data = json_decode($message->body, true);

            if (!$data || !isset($data['phone_number'], $data['otp'])) {
                Log::error('Invalid SMS notification message', ['message' => $message->body]);
                $channel->basic_ack($message->get('delivery_tag'));
                return;
            }

            Log::info('Processing SMS notification', [
                'phone_number' => $data['phone_number'],
                'user_id' => $data['user_id'] ?? 'unknown'
            ]);

            if (!$this->smsService->isConfigured()) {
                Log::error('SMS service not configured', ['phone_number' => $data['phone_number']]);
                $channel->basic_ack($message->get('delivery_tag'));
                return;
            }

            $result = $this->smsService->sendOtp($data['phone_number'], $data['otp']);

            if ($result['success']) {
                Log::info('SMS notification sent successfully', [
                    'phone_number' => $data['phone_number'],
                    'user_id' => $data['user_id'] ?? 'unknown'
                ]);
            } else {
                Log::error('Failed to send SMS', [
                    'phone_number' => $data['phone_number'],
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }

            // Acknowledge the message to remove it from queue
            $channel->basic_ack($message->get('delivery_tag'));
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'error' => $e->getMessage(),
                'message' => $message->body
            ]);
            // Reject the message and requeue it
            $channel->basic_nack($message->get('delivery_tag'), false, true);
        }

    }
    
}
