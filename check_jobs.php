<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get jobs from database
$jobs = DB::table('jobs')->select('id', 'queue', 'attempts', 'created_at')->get();

echo "Current jobs in database:\n";
foreach($jobs as $job) {
    echo "ID: " . $job->id . " Queue: " . $job->queue . " Attempts: " . $job->attempts . " Created: " . $job->created_at . "\n";
}

echo "Total jobs: " . count($jobs) . "\n";
