<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Services\NotificationPublisherService;
use App\Services\RabbitMQService;

// Create a mock RabbitMQ service that always fails
class FailingRabbitMQService
{
    public function isConnected()
    {
        return false;
    }

    public function reconnect()
    {
        throw new Exception('Failed to reconnect to RabbitMQ');
    }

    public function publish($exchange, $routingKey, $message, $persistent = true)
    {
        return false;
    }
}

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get a user from the database
$user = User::first();
if (!$user) {
    echo "No user found in the database\n";
    exit(1);
}

// Create a failing RabbitMQ service
$failingRabbitMQService = new FailingRabbitMQService();

// Create the notification publisher service manually
class TestNotificationPublisherService extends NotificationPublisherService
{
    protected $rabbitMQService;

    public function __construct($rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }
}

$notificationService = new TestNotificationPublisherService($failingRabbitMQService);

// Test the fallback mechanism
echo "Testing notification fallback mechanism...\n";
echo "User: " . $user->email . "\n";

try {
    // Test registration notification
    $result = $notificationService->publishRegistrationNotification($user, '123456');
    echo "Registration notification result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

    // Test OTP resend notification
    $result = $notificationService->publishOtpResendNotification($user, '654321');
    echo "OTP resend notification result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

    echo "Test completed. Check logs for details.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
