<?php

namespace App\Console\Commands;

use App\Services\NotificationConsumerService;
use App\Services\RabbitMQService;
use App\Services\SmService;
use Illuminate\Console\Command;

class SmsConsumer extends Command
{
    protected $signature = 'rabbitmq:consume:sms';
    protected $description = 'Consume SMS notifications from RabbitMQ';

    public function handle()
    {
        $this->info('Starting SMS notification consumer...');

        try {
            $rabbitMQService = new RabbitMQService();
            $smsService = new SmService();
            $consumerService = new NotificationConsumerService($smsService);

            $this->info('Waiting for SMS notifications...');

            $callback = function ($channel, $message) use ($consumerService) {
                $consumerService->processSmsNotification($channel, $message);
            };

            $rabbitMQService->consume('sms.notifications', $callback);

        } catch (\Exception $e) {
            $this->error('SMS consumer error: ' . $e->getMessage());
            return 1;
        }

    }



    
}
