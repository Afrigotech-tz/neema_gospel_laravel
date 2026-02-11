<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTestSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::create([
            'title' => 'Test Event',
            'type' => 'concert',
            'date' => now()->addDays(30)->toDateString(),
            'location' => 'Test Venue'
        ]);

        TicketType::create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50.00,
            'quantity' => 50
        ]);

        TicketType::create([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price' => 100.00,
            'quantity' => 20
        ]);

        
        echo "Test data created. Event ID: {$event->id}\n";


    }

   
}


