<?php

return [
    'kilakona' => [
        'url' => env('SMS_KILAKONA_URL', 'https://messaging.kilakona.co.tz/api/v1/send-message'),
        'api_key' => env('SMS_KILAKONA_API_KEY'),
        'api_secret' => env('SMS_KILAKONA_API_SECRET'),
        'sender_id' => env('SMS_KILAKONA_SENDER_ID', 'infoS'),
        'delivery_report_url' => env('SMS_DELIVERY_REPORT_URL', 'https://your-server.com/delivery-callback'),
    ],
];
