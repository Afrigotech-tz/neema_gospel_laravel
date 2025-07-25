<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationPublisherService
{
    protected $rabbitMQService;

    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

    public function publishRegistrationNotification(User $user, string $otp)
    {
        $message = [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'surname' => $user->surname,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'verification_method' => $user->verification_method,
            'otp' => $otp,
            'timestamp' => now()->toISOString(),
        ];

        // For SMS notifications, publish directly to sms.notifications queue
        if ($user->verification_method === 'mobile') {
            $success = $this->rabbitMQService->publish(
                'sms.notifications',
                'sms.notification',
                $message
            );
        } else {
            // For email notifications, use the email queue
            $success = $this->rabbitMQService->publish(
                'email.notifications',
                'email.notification',
                $message
            );
        }

        if (!$success) {
            Log::error('Failed to publish notification', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);
            return false;
        }

        Log::info('Notification published to RabbitMQ', [
            'user_id' => $user->id,
            'verification_method' => $user->verification_method,
            'queue' => $user->verification_method === 'mobile' ? 'sms.notifications' : 'email.notifications'
        ]);

        return true;
    }

    public function publishOtpResendNotification(User $user, string $otp)
    {
        return $this->publishRegistrationNotification($user, $otp);
    }


}
