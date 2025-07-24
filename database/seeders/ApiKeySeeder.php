<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Flutter App API Key
        ApiKey::create([
            'name' => 'Flutter Mobile App',
            'key' => 'mh8bUvdGP2xD9P4J3BZPYvr6noPBwEwZ',
            'client_type' => 'flutter',
            'is_active' => true,
            'rate_limit' => 1000, // 1000 requests per day
            'expires_at' => now()->addYear(),
        ]);

        // React App API Key
        ApiKey::create([
            'name' => 'React Web App',
            'key' => 'h67vhksEYwSV1OTrqK6TTNs36uU5DxOT',
            'client_type' => 'react',
            'is_active' => true,
            'rate_limit' => 2000, // 2000 requests per day
            'expires_at' => now()->addYear(),
        ]);

        // Development Keys
        ApiKey::create([
            'name' => 'Development Flutter',
            'key' => 'dev_flutter_key_2024_dev_mode_11111',
            'client_type' => 'flutter',
            'is_active' => true,
            'rate_limit' => 5000, // Higher limit for development
            'expires_at' => now()->addMonths(6),
        ]);

        ApiKey::create([
            'name' => 'Development React',
            'key' => 'Mgg0ufnqzjirjjyva3SrjIDB0XDs6GOM',
            'client_type' => 'react',
            'is_active' => true,
            'rate_limit' => 5000, // Higher limit for development
            'expires_at' => now()->addMonths(6),
        ]);
        

    }

}
