<?php

namespace App\Console\Commands;

use App\Services\NotificationConsumerService;
use App\Services\RabbitMQService;
use App\Services\SmService;
use Illuminate\Console\Command;

class EmailConsumer extends Command
{
    protected $signature = 'rabbitmq:consume:email';
    protected $description = 'Consume email notifications from RabbitMQ';

    public function handle()
    {
        $this->info('Starting email notification consumer...');

        try {
            $rabbitMQService = new RabbitMQService();
            $smsService = new SmService();
            $consumerService = new NotificationConsumerService($smsService);

            $this->info('Waiting for email notifications...');

            $callback = function ($channel, $message) use ($consumerService) {
                $consumerService->processEmailNotification($channel, $message);
            };

            $rabbitMQService->consume('email.notifications', $callback);

        } catch (\Exception $e) {
            $this->error('Email consumer error: ' . $e->getMessage());
            return 1;
        }

    }

}
