<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing news to avoid duplicates
        News::truncate();

        $newsData = [
            [
                'title' => 'Night of Encounter - February 5, 2022',
                'slug' => 'night-of-encounter-february-5-2022-' . uniqid(),
                'content' => 'Join us for a powerful night of worship and encounter with God. Experience His presence like never before.',
                'excerpt' => 'A powerful night of worship and encounter with God.',
                'featured_image' => 'news/night-of-encounter.jpg',
                'published_at' => now()->subDays(30),
                'category' => 'event',
                'type' => 'event',
                'duration' => 240,
                'location' => 'Neema Gospel Church',
                'is_featured' => true,
                'author' => 'Pastor John Doe',
                'tags' => ['worship', 'encounter', 'prayer'],
            ],
            [
                'title' => 'Recently News - Church Anniversary Celebration',
                'slug' => 'church-anniversary-celebration-' . uniqid(),
                'content' => 'We are celebrating 10 years of God\'s faithfulness at Neema Gospel. Join us for a special service.',
                'excerpt' => 'Celebrating 10 years of God\'s faithfulness.',
                'featured_image' => 'news/anniversary.jpg',
                'published_at' => now()->subDays(15),
                'category' => 'news',
                'type' => 'article',
                'author' => 'Church Admin',
                'tags' => ['anniversary', 'celebration', 'milestone'],
            ],
            [
                'title' => 'New Music Release - "Victory Song"',
                'slug' => 'new-music-release-victory-song-' . uniqid(),
                'content' => 'Our choir has released a new song titled "Victory Song". Available on all streaming platforms.',
                'excerpt' => 'New music release from our choir.',
                'featured_image' => 'news/music-release.jpg',
                'published_at' => now()->subDays(7),
                'category' => 'announcement',
                'type' => 'article',
                'author' => 'Music Ministry',
                'tags' => ['music', 'release', 'choir'],
            ],
            [
                'title' => 'Youth Conference 2024 - Save the Date',
                'slug' => 'youth-conference-2024-save-the-date-' . uniqid(),
                'content' => 'Mark your calendars for our annual youth conference. More details coming soon!',
                'excerpt' => 'Annual youth conference announcement.',
                'featured_image' => 'news/youth-conference.jpg',
                'published_at' => now()->addDays(30),
                'category' => 'event',
                'type' => 'event',
                'duration' => 480,
                'location' => 'Neema Gospel Youth Center',
                'author' => 'Youth Ministry',
                'tags' => ['youth', 'conference', 'fellowship'],
            ],
            [
                'title' => 'Weekly Prayer Meeting Schedule Update',
                'slug' => 'weekly-prayer-meeting-schedule-update-' . uniqid(),
                'content' => 'Please note the updated schedule for our weekly prayer meetings. Every Wednesday at 7:00 PM.',
                'excerpt' => 'Updated prayer meeting schedule.',
                'published_at' => now()->subDays(3),
                'category' => 'announcement',
                'type' => 'article',
                'author' => 'Prayer Team',
                'tags' => ['prayer', 'schedule', 'update'],
            ],
        ];

        foreach ($newsData as $data) {
            News::create($data);
        }
    }
}
