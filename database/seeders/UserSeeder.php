<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some countries for foreign key relationships
        $tanzania = Country::where('code', 'TZ')->first();
        $kenya = Country::where('code', 'KE')->first();
        $uganda = Country::where('code', 'UG')->first();
        $usa = Country::where('code', 'US')->first();

        $users = [
            [
                'first_name' => 'John',
                'surname' => 'Doe',
                'gender' => 'male',
                'phone_number' => '+255712345678',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('password123'),
                'country_id' => $tanzania->id,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Jane',
                'surname' => 'Smith',
                'gender' => 'female',
                'phone_number' => '+254712345678',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('password123'),
                'country_id' => $kenya->id,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Michael',
                'surname' => 'Johnson',
                'gender' => 'male',
                'phone_number' => '+256712345678',
                'email' => 'michael.johnson@example.com',
                'password' => Hash::make('password123'),
                'country_id' => $uganda->id,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Sarah',
                'surname' => 'Williams',
                'gender' => 'female',
                'phone_number' => '+1234567890',
                'email' => 'sarah.williams@example.com',
                'password' => Hash::make('password123'),
                'country_id' => $usa->id,
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'David',
                'surname' => 'Brown',
                'gender' => 'male',
                'phone_number' => '+255787654321',
                'email' => 'david.brown@example.com',
                'password' => Hash::make('password123'),
                'country_id' => $tanzania->id,
                'email_verified_at' => null,
            ],

        ];

        foreach ($users as $user) {
            User::create($user);
        }

    }

}
