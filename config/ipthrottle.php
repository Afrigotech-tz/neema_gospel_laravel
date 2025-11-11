<?php

return [
    
    'thresholds' => [
        'login' => ['soft' => 100000, 'hard' => 1000000, 'ban_seconds' => 30],  // seconds 
        'sensitive' => ['soft' => 1000000, 'hard' => 100000, 'ban_seconds' => 3600 * 6],  // 6 hours
        'api_write' => ['soft' => 1000000, 'hard' => 100000, 'ban_seconds' => 3600],
        'api_read' => ['soft' => 10000000, 'hard' => 100000, 'ban_seconds' => 600],
        'default' => ['soft' => 60000, 'hard' => 1000000, 'ban_seconds' => 3600],
    ],


];




