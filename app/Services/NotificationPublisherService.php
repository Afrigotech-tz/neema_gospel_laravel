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

        $routingKey = 'user.registered.' . $user->verification_method;
        $exchange = config('rabbitmq.exchanges.user_registration.name');

        $success = $this->rabbitMQService->publish($exchange, $routingKey, $message);

        if (!$success) {
            Log::error('Failed to publish registration notification', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);

            // Fallback to synchronous sending if RabbitMQ fails
            return false;
        }

        Log::info('Registration notification published to RabbitMQ', [
            'user_id' => $user->id,
            'verification_method' => $user->verification_method
        ]);

        return true;
    }

    public function publishOtpResendNotification(User $user, string $otp)
    {
        return $this->publishRegistrationNotification($user, $otp);
    }


}
