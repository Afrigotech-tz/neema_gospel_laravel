<?php

namespace App\Listeners;

use App\Events\UserMessageCreated;
use App\Models\UserMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessUserMessage implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserMessageCreated $event): void
    {
        try {
            // Save the message to database
            $data = $event->messageData;
            $data['status'] = 'pending'; // Default status

            $message = UserMessage::create($data);

            // Log the successful creation
            Log::info('User message created successfully', [
                'message_id' => $message->id,
                'email' => $message->email,
                'subject' => $message->subject
            ]);

            // Here you could also send an email notification to admins
            // For example:
            // Mail::to('admin@example.com')->send(new NewUserMessageNotification($message));

        } catch (\Exception $e) {
            Log::error('Failed to process user message', [
                'error' => $e->getMessage(),
                'data' => $event->messageData
            ]);

            // You might want to throw the exception or handle it differently
            throw $e;
        }
    }
}
