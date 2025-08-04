<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DonationCategory;

class DonationCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Education',
            'Health',
            'Emergency Relief',
            'Community Development',
            'Orphan Support',
            'Water & Sanitation',
            'Food Assistance'
        ];

        foreach ($categories as $name) {
            DonationCategory::create(['name' => $name]);
        }
    }
}
