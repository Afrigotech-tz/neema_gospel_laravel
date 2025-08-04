<?php

use Illuminate\Container\Attributes\DB;
use Illuminate\Support\Facades\Artisan;

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get a user
$user = App\Models\User::find(1);

if (!$user) {
    echo "No user found with ID 1\n";
    exit(1);
}

// Dispatch a job
echo "Dispatching job...\n";
App\Jobs\SendNotificationJob::dispatch($user, '123456', 'email');
echo "Job dispatched successfully\n";

// Check job count
$jobCount = DB::table('jobs')->count();
echo "Total jobs in queue: $jobCount\n";

// Try to process one job
echo "Processing job...\n";
Artisan::call('queue:work', [
    '--once' => true,
    '--verbose' => true
]);

echo "Job processing completed\n";

// Check job count again
$jobCount = DB::table('jobs')->count();
echo "Total jobs in queue after processing: $jobCount\n";
