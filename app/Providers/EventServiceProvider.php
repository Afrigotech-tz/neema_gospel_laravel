<?php

namespace App\Providers;

use App\Events\PasswordResetRequested;
use App\Events\ResendOTPcode;
use App\Events\UserMessageCreated;
use App\Events\UserRegistered;
use App\Listeners\ProcessUserMessage;
use App\Listeners\SendOtpNotification;
use App\Listeners\SendPasswordResetLink;
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
        // UserRegistered::class => [
        //     SendOtpNotification::class,
        // ],
        // ResendOTPcode::class => [
        //     SendOtpNotification::class,
        // ],
        \App\Events\UserRegistered::class => [
            \App\Listeners\SendOtpNotification::class,
        ],
        \App\Events\ResendOTPcode::class => [
            \App\Listeners\SendOtpNotification::class,
        ],
        PasswordResetRequested::class => [
            SendPasswordResetLink::class,
        ],
        UserMessageCreated::class => [
            ProcessUserMessage::class,
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
