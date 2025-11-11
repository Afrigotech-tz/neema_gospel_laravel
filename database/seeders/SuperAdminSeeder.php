<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Tanzania country for the super admin
        $tanzania = Country::where('code', 'TZ')->first();

        // Create super admin user
        $superAdmin = User::create([
            'first_name' => 'Super',
            'surname' => 'Admin',
            'gender' => 'male',
            'phone_number' => '+255700000000',
            'email' => 'superadmin@neemagospel.com',
            'password' => Hash::make('password'),
            'country_id' => $tanzania->id,
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assign super_admin role

        $superAdmin->assignRole('super_admin');


    }

    


}
