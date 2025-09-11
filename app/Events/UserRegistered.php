<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public $user;
    public $otp;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $otp
     * 
     */

    public function __construct(User $user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }



}



