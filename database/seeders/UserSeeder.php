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
                'password' => Hash::make('password'),
                'country_id' => $tanzania->id,
                'email_verified_at' => now(),
            ],

        ];

        foreach ($users as $user) {
            User::create($user);
        }

    }

    

}
