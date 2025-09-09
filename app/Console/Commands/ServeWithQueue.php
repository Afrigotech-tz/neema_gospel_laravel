<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ServeWithQueue extends Command
{
    protected $signature = 'run';
    protected $description = 'Run Laravel server and queue worker together';

    public function handle()
    {
        $this->info('Starting queue worker...');
        // Start queue worker in background
        exec('start cmd /k "php artisan queue:work"'); // Windows
        // Or for Linux/macOS: exec('php artisan queue:work > /dev/null 2>&1 &');

        $this->info('Starting Laravel server...');
        $this->call('serve', [
            '--host' => '127.0.0.1',
            '--port' => '8000',
        ]);
    }

}


