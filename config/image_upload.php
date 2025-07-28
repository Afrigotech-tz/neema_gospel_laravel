<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for image upload quality and processing settings
    |
    */

    'quality' => [
        'profile_pictures' => 95,
        'product_images' => 90,
        'news_images' => 90,
        'music_covers' => 95,
        'event_images' => 90,
    ],

    'dimensions' => [
        'profile_pictures' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'resize' => false, // Keep original dimensions
        ],
        'product_images' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'resize' => true,
        ],
        'news_images' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'resize' => true,
        ],
        'music_covers' => [
            'max_width' => 800,
            'max_height' => 800,
            'resize' => true,
        ],
        'event_images' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'resize' => true,
        ],
    ],

    'formats' => [
        'enabled' => true,
        'preferred_format' => 'webp',
        'fallback_format' => 'jpg',
    ],

    'backup' => [
        'enabled' => true,
        'keep_original' => true,
        'backup_directory' => 'originals',
    ],

    'compression' => [
        'jpeg_quality' => 95,
        'png_compression' => 6,
        'webp_quality' => 95,
    ],
];
