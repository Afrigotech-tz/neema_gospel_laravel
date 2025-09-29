<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMessageCreated
{
    use Dispatchable, SerializesModels;

    public $messageData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    
}
