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
            'key' => 'flutter_app_key_2024_secure_key_12345',
            'client_type' => 'flutter',
            'is_active' => true,
            'rate_limit' => 1000, // 1000 requests per day
            'expires_at' => now()->addYear(),
        ]);

        // React App API Key
        ApiKey::create([
            'name' => 'React Web App',
            'key' => 'react_app_key_2024_secure_key_67890',
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
            'key' => 'dev_react_key_2024_dev_mode_22222',
            'client_type' => 'react',
            'is_active' => true,
            'rate_limit' => 5000, // Higher limit for development
            'expires_at' => now()->addMonths(6),
        ]);

    }
    
}
