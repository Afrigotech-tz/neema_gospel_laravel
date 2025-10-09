<?php

return [
    // thresholds per endpoint type (requests per minute)
    'thresholds' => [
        'login' => ['soft' => 5, 'hard' => 30, 'ban_seconds' => 30],  // seconds 
        'sensitive' => ['soft' => 4, 'hard' => 60, 'ban_seconds' => 3600 * 6],  // 6 hours
        'api_write' => ['soft' => 30, 'hard' => 120, 'ban_seconds' => 3600],
        'api_read' => ['soft' => 300, 'hard' => 1500, 'ban_seconds' => 600],
        'default' => ['soft' => 60, 'hard' => 300, 'ban_seconds' => 3600],
    ],



];




