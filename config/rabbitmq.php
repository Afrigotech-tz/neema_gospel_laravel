<?php

return [
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'username' => env('RABBITMQ_USERNAME', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    // Connection timeout settings
    'connection_timeout' => env('RABBITMQ_CONNECTION_TIMEOUT', 30),
    'read_write_timeout' => env('RABBITMQ_READ_WRITE_TIMEOUT', 60),
    'heartbeat' => env('RABBITMQ_HEARTBEAT', 20),
    'keepalive' => env('RABBITMQ_KEEPALIVE', false), // Disabled for Windows compatibility

    // Consumer timeout settings
    'consumer_timeout' => env('RABBITMQ_CONSUMER_TIMEOUT', 60),

    'exchanges' => [
        'user_registration' => [
            'name' => 'user.registration.v2',
            'type' => 'topic',
            'durable' => true,
            'auto_delete' => false,
            'internal' => false,
        ],
    ],

    'queues' => [
        'email_notifications' => [
            'name' => 'email.notifications',
            'durable' => true,
            'auto_delete' => false,
        ],
        'sms_notifications' => [
            'name' => 'sms.notifications',
            'durable' => true,
            'auto_delete' => false,
        ],
    ],

    'bindings' => [
        'email_notifications' => [
            'exchange' => 'user.registration',
            'routing_key' => 'user.registered.email',
        ],
        'sms_notifications' => [
            'exchange' => 'user.registration',
            'routing_key' => 'user.registered.sms',
        ],
        'sms_notifications_mobile' => [
            'exchange' => 'user.registration',
            'routing_key' => 'user.registered.mobile',
        ],
    ],
];
