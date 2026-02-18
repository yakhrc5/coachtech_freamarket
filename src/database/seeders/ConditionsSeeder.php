<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $conditions = [
            ['name' => '良好', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '目立った傷や汚れなし', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'やや傷や汚れあり', 'created_at' => $now, 'updated_at' => $now],
            ['name' => '状態が悪い', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('conditions')->insert($conditions);
    }
}
