<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PasswordResetRequested $event): void
    {
        $user = $event->user;

        // Always trigger reset using the user's email
        Password::sendResetLink(['email' => $user->email]);

    }


    
}


