<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            UserProfile::create([
                'user_id' => $user->id,
                'bio' => 'Hello! I am ' . $user->first_name . ' ' . $user->surname . '. Welcome to Neema Gospel!',
                'occupation' => 'Member',
                'profile_public' => true,
                'location_public' => false,
            ]);
        }
    }

}

