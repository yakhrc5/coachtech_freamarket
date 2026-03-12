<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $paymentMethods = [
            [
                'name' => 'コンビニ支払い',
                'code' => 'konbini',
                'stripe_code' => 'konbini',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'カード支払い',
                'code' => 'card',
                'stripe_code' => 'card',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('payment_methods')->insert($paymentMethods);
    }
}
