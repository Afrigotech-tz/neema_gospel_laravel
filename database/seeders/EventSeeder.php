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
                'type' => 'concert',
                'date' => now()->addDays(2)->setTime(18, 0, 0),
                'location' => 'CCC Upanga, Dar es Salaam',
                'picture' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=800&q=80',
            ],
            [
                'title' => 'Sunday Service',
                'type' => 'service',
                'date' => now()->addDays(4)->setTime(9, 0, 0),
                'location' => 'AICT - Chang\'ombe, Dar es Salaam',
                'picture' => 'https://images.unsplash.com/photo-1507699622108-4be3abd695ad?w=800&q=80',
            ],
            [
                'title' => 'Live Recording Session',
                'type' => 'live_recording',
                'date' => now()->addDays(7)->setTime(14, 0, 0),
                'location' => 'Neema Gospel Studio, Mikocheni',
                'picture' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800&q=80',
            ],
            [
                'title' => 'Youth Conference 2024',
                'type' => 'conference',
                'date' => now()->addDays(14)->setTime(8, 0, 0),
                'location' => 'Julius Nyerere International Convention Centre, Dar es Salaam',
                'picture' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80',
            ],
            [
                'title' => 'Healing & Deliverance Service',
                'type' => 'service',
                'date' => now()->addDays(10)->setTime(17, 0, 0),
                'location' => 'National Stadium, Dar es Salaam',
                'picture' => 'https://images.unsplash.com/photo-1515169067868-5387ec356754?w=800&q=80',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}
