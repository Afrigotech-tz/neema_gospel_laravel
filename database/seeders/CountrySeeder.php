<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Tanzania', 'code' => 'TZ', 'dial_code' => '+255'],
            ['name' => 'Kenya', 'code' => 'KE', 'dial_code' => '+254'],
            ['name' => 'Uganda', 'code' => 'UG', 'dial_code' => '+256'],
            ['name' => 'Rwanda', 'code' => 'RW', 'dial_code' => '+250'],
            ['name' => 'Burundi', 'code' => 'BI', 'dial_code' => '+257'],
            ['name' => 'South Sudan', 'code' => 'SS', 'dial_code' => '+211'],
            ['name' => 'Democratic Republic of Congo', 'code' => 'CD', 'dial_code' => '+243'],
            ['name' => 'United States', 'code' => 'US', 'dial_code' => '+1'],
            ['name' => 'United Kingdom', 'code' => 'GB', 'dial_code' => '+44'],
            ['name' => 'Germany', 'code' => 'DE', 'dial_code' => '+49'],
            ['name' => 'France', 'code' => 'FR', 'dial_code' => '+33'],
            ['name' => 'Canada', 'code' => 'CA', 'dial_code' => '+1'],
            ['name' => 'Australia', 'code' => 'AU', 'dial_code' => '+61'],
            ['name' => 'South Africa', 'code' => 'ZA', 'dial_code' => '+27'],
            ['name' => 'Nigeria', 'code' => 'NG', 'dial_code' => '+234'],
            ['name' => 'Ghana', 'code' => 'GH', 'dial_code' => '+233'],
            ['name' => 'Egypt', 'code' => 'EG', 'dial_code' => '+20'],
            ['name' => 'Morocco', 'code' => 'MA', 'dial_code' => '+212'],
            ['name' => 'Ethiopia', 'code' => 'ET', 'dial_code' => '+251'],
            ['name' => 'Zambia', 'code' => 'ZM', 'dial_code' => '+260'],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }

        
    }
}
