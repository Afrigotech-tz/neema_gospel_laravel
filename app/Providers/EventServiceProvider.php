<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Register your event and listener here
        
        \App\Events\UserRegistered::class => [
            \App\Listeners\SendOtpNotification::class,
        ],

        \App\Events\PasswordResetRequested::class => [
            \App\Listeners\SendPasswordResetLink::class,
        ],


    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
