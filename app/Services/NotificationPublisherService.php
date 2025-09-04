<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
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

        // Check if RabbitMQ is connected before attempting to publish
        if (!$this->rabbitMQService->isConnected()) {
            Log::warning('RabbitMQ is not connected, attempting to reconnect', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);

            try {
                $this->rabbitMQService->reconnect();
                Log::info('RabbitMQ reconnected successfully', [
                    'user_id' => $user->id,
                    'verification_method' => $user->verification_method
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to reconnect to RabbitMQ, falling back to Laravel queue', [
                    'user_id' => $user->id,
                    'verification_method' => $user->verification_method,
                    'error' => $e->getMessage()
                ]);

                // Fallback to Laravel queue
                return $this->sendViaLaravelQueue($user, $otp, $user->verification_method);

            }
        }

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
            Log::error('Failed to publish notification to RabbitMQ, falling back to Laravel queue', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);

            // Fallback to Laravel queue
            return $this->sendViaLaravelQueue($user, $otp, $user->verification_method);
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
        $message = [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'surname' => $user->surname,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'verification_method' => $user->verification_method,
            'otp' => $otp,
            'timestamp' => now()->toISOString(),
            'resend' => true,
        ];

        // Check if RabbitMQ is connected before attempting to publish
        if (!$this->rabbitMQService->isConnected()) {
            Log::warning('RabbitMQ is not connected for OTP resend, attempting to reconnect', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);

            try {
                $this->rabbitMQService->reconnect();
                Log::info('RabbitMQ reconnected successfully for OTP resend', [
                    'user_id' => $user->id,
                    'verification_method' => $user->verification_method
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to reconnect to RabbitMQ for OTP resend, falling back to Laravel queue', [
                    'user_id' => $user->id,
                    'verification_method' => $user->verification_method,
                    'error' => $e->getMessage()
                ]);

                // Fallback to Laravel queue
                return $this->sendViaLaravelQueue($user, $otp, $user->verification_method);

            }

        }
        
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
            Log::error('Failed to publish OTP resend notification to RabbitMQ, falling back to Laravel queue', [
                'user_id' => $user->id,
                'verification_method' => $user->verification_method
            ]);

            // Fallback to Laravel queue
            return $this->sendViaLaravelQueue($user, $otp, $user->verification_method);
        }

        Log::info('OTP resend notification published to RabbitMQ', [
            'user_id' => $user->id,
            'verification_method' => $user->verification_method,
            'queue' => $user->verification_method === 'mobile' ? 'sms.notifications' : 'email.notifications'
        ]);

        return true;
    }

    /**
     * Send notification via Laravel queue as fallback
     *
     * @param User $user
     * @param string $otp
     * @param string $notificationType
     * @return bool
     */
    protected function sendViaLaravelQueue(User $user, string $otp, string $notificationType)
    {
        try {
            // Dispatch job to Laravel queue
            SendNotificationJob::dispatch($user, $otp, $notificationType === 'mobile' ? 'sms' : 'email')
                ->onQueue(config('queue.default'));

            Log::info('Notification sent via Laravel queue as fallback', [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
                'queue_connection' => config('queue.default'),
                'queue_name' => config('queue.connections.' . config('queue.default') . '.queue', 'default')
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification via Laravel queue', [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
                'queue_connection' => config('queue.default')
            ]);

            return false;
        }
    }
}
