<?php

namespace App\Console\Commands;

use App\Services\RabbitMQService;
use Illuminate\Console\Command;

class SetupRabbitMQ extends Command
{
    protected $signature = 'rabbitmq:setup';
    protected $description = 'Setup RabbitMQ exchanges, queues and bindings';

    public function handle()
    {
        $this->info('Setting up RabbitMQ infrastructure...');

        try {
            // Define missing socket constants for Windows compatibility
            if (!defined('SOCKET_EAGAIN')) {
                define('SOCKET_EAGAIN', 11);
            }
            if (!defined('SOCKET_EWOULDBLOCK')) {
                define('SOCKET_EWOULDBLOCK', 11);
            }
            if (!defined('SOCKET_EINTR')) {
                define('SOCKET_EINTR', 4);
            }

            $rabbitMQService = new RabbitMQService();
            $rabbitMQService->setupExchangesAndQueues();

            $this->info('RabbitMQ setup completed successfully!');
            $this->info('Exchanges: user.registration');
            $this->info('Queues: email.notifications, sms.notifications');
            $this->info('Bindings: email.notifications -> user.registered.email');
            $this->info('Bindings: sms.notifications -> user.registered.sms');

        } catch (\Exception $e) {
            $this->error('Failed to setup RabbitMQ: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
    

}

