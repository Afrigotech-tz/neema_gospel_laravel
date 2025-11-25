<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeWithQueue extends Command
{
    protected $signature = 'run:events';
    protected $description = 'Run Laravel server and queue worker together (non-stop)';

    public function handle()
    {
        $this->info('Starting persistent queue worker...');

        // Start queue worker process
        $queueProcess = new Process(['php', 'artisan', 'queue:work', '--tries=3', '--timeout=0']);
        $queueProcess->setTimeout(null);
        $queueProcess->start();

        // Monitor queue output in background
        while ($queueProcess->isRunning()) {
            echo $queueProcess->getIncrementalOutput();
            echo $queueProcess->getIncrementalErrorOutput();
            usleep(500000); // 0.5 second
        }

        // If queue stops, restart it
        if (!$queueProcess->isRunning()) {
            $this->warn("Queue worker stopped! Restarting...");
            $this->handle(); // Restart the worker
        }

        $this->info('Starting Laravel server...');

        // Run Laravel server in main process
        $this->call('serve', [
            '--host' => '127.0.0.1',
            '--port' => '8000',
        ]);
    }
}
