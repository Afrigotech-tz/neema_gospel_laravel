<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeWithQueue extends Command
{
    protected $signature = 'run';
    protected $description = 'Run Laravel server and queue worker together';

    public function handle()
    {
        $this->info('Starting queue worker...');

        // Run queue worker in the background
        $queueProcess = new Process(['php', 'artisan', 'queue:work']);
        $queueProcess->start();

        $this->info('Starting Laravel server...');

        // Run the Laravel server in the current terminal
        $this->call('serve', [
            '--host' => '127.0.0.1',
            '--port' => '8000',
        ]);

        // Keep the queue process running alongside the server
        $queueProcess->wait();

    }

}


