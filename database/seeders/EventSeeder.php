<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Sing With Thanks Gospel Concert',
                'description' => 'Join us for a powerful night of worship and praise with renowned gospel artists. Experience the presence of God through music and testimonies.',
                'type' => 'concert',
                'start_date' => now()->addDays(2)->setTime(18, 0, 0),
                'end_date' => now()->addDays(2)->setTime(22, 0, 0),
                'venue' => 'CCC Upanga',
                'location' => 'Upanga, Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.7924,
                'longitude' => 39.2083,
                'capacity' => 20000,
                'attendees_count' => 20500,
                'is_featured' => true,
                'is_public' => true,
                'status' => 'upcoming',
                'ticket_price' => 0.00,
                'tags' => ['gospel', 'concert', 'worship', 'praise', 'music'],
                'metadata' => [
                    'organizer' => 'Neema Gospel Ministry',
                    'contact' => '+255712345678',
                    'website' => 'https://neemagospel.or.tz',
                ],
            ],
            [
                'title' => 'Sunday Service',
                'description' => 'Join us for our regular Sunday service filled with powerful worship, inspiring messages, and community fellowship.',
                'type' => 'service',
                'start_date' => now()->addDays(4)->setTime(9, 0, 0),
                'end_date' => now()->addDays(4)->setTime(12, 0, 0),
                'venue' => 'AICT - Chang\'ombe',
                'location' => 'Chang\'ombe, Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.8536,
                'longitude' => 39.2735,
                'capacity' => 20000,
                'attendees_count' => 20000,
                'is_featured' => true,
                'is_public' => true,
                'status' => 'upcoming',
                'ticket_price' => 0.00,
                'tags' => ['sunday', 'service', 'worship', 'fellowship', 'church'],
                'metadata' => [
                    'organizer' => 'Neema Gospel Ministry',
                    'contact' => '+255712345678',
                    'dress_code' => 'Smart casual',
                ],
            ],
            [
                'title' => 'Live Recording Session',
                'description' => 'Experience the power of live gospel music recording with our worship team. Be part of creating anointed music that will bless nations.',
                'type' => 'live_recording',
                'start_date' => now()->addDays(7)->setTime(14, 0, 0),
                'end_date' => now()->addDays(7)->setTime(18, 0, 0),
                'venue' => 'Neema Gospel Studio',
                'location' => 'Mikocheni, Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.7628,
                'longitude' => 39.2423,
                'capacity' => 500,
                'attendees_count' => 450,
                'is_featured' => false,
                'is_public' => true,
                'status' => 'upcoming',
                'ticket_price' => 5000.00,
                'ticket_url' => 'https://neemagospel.or.tz/events/live-recording',
                'tags' => ['live', 'recording', 'music', 'studio', 'worship'],
                'metadata' => [
                    'organizer' => 'Neema Gospel Music Ministry',
                    'contact' => '+255712345679',
                    'recording_type' => 'Album recording',
                ],
            ],
            [
                'title' => 'Youth Conference 2024',
                'description' => 'Empowering the next generation through biblical teachings, worship, and fellowship. Special guest speakers and powerful sessions.',
                'type' => 'conference',
                'start_date' => now()->addDays(14)->setTime(8, 0, 0),
                'end_date' => now()->addDays(16)->setTime(17, 0, 0),
                'venue' => 'Julius Nyerere International Convention Centre',
                'location' => 'Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.7924,
                'longitude' => 39.2083,
                'capacity' => 5000,
                'attendees_count' => 3200,
                'is_featured' => true,
                'is_public' => true,
                'status' => 'upcoming',
                'ticket_price' => 15000.00,
                'ticket_url' => 'https://neemagospel.or.tz/events/youth-conference',
                'tags' => ['youth', 'conference', 'empowerment', 'teaching', 'fellowship'],
                'metadata' => [
                    'organizer' => 'Neema Gospel Youth Ministry',
                    'contact' => '+255712345680',
                    'theme' => 'Arise and Shine',
                ],
            ],
            [
                'title' => 'Healing & Deliverance Service',
                'description' => 'A special service focused on healing, deliverance, and breakthrough. Come expecting miracles and transformation.',
                'type' => 'service',
                'start_date' => now()->addDays(10)->setTime(17, 0, 0),
                'end_date' => now()->addDays(10)->setTime(20, 0, 0),
                'venue' => 'National Stadium',
                'location' => 'Dar es Salaam',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'latitude' => -6.8536,
                'longitude' => 39.2735,
                'capacity' => 60000,
                'attendees_count' => 45000,
                'is_featured' => true,
                'is_public' => true,
                'status' => 'upcoming',
                'ticket_price' => 0.00,
                'tags' => ['healing', 'deliverance', 'miracles', 'breakthrough', 'special'],
                'metadata' => [
                    'organizer' => 'Neema Gospel Healing Ministry',
                    'contact' => '+255712345681',
                    'special_guest' => 'International Evangelist',
                ],
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}
