<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            EventSeeder::class,
            NewsSeeder::class,
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            ApiKeySeeder::class,
            DonationCategorySeeder::class,
            ProductCategorySeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
