<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Credit Card',
                'type' => 'credit_card',
                'provider' => 'stripe',
                'code' => 'credit_card',
                'description' => 'Pay using Visa, Mastercard, or American Express',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mobile Money',
                'type' => 'mobile_money',
                'provider' => 'mobile_money',
                'code' => 'mobile_money',
                'description' => 'Pay using mobile money services',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'M-Pesa',
                'type' => 'mobile_money',
                'provider' => 'vodacom',
                'code' => 'mpesa',
                'description' => 'Pay using M-Pesa mobile money service',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'AirtelMoney',
                'type' => 'mobile_money',
                'provider' => 'airtel',
                'code' => 'airtel_money',
                'description' => 'Pay using AirtelMoney mobile money service',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mix-By-Yas',
                'type' => 'mobile_money',
                'provider' => 'yas',
                'code' => 'mix_by_yas',
                'description' => 'Pay using Mix-By-Yas mobile money service',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Halopesa',
                'type' => 'mobile_money',
                'provider' => 'halotel',
                'code' => 'halopesa',
                'description' => 'Pay using Halopesa mobile money service',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_methods')->insert($paymentMethods);
    }

}

