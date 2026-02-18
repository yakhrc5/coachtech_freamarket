<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // 出品者（UsersSeederで user_id=1 が存在する前提）
        $sellerId = 1;

        $conditionMap = DB::table('conditions')->pluck('id', 'name');
        $categoryMap = DB::table('categories')->pluck('id', 'name');

        $items = [
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'condition_name' => '良好',
                'image_path' => 'items/watch.jpg',
                'category_names' => ['ファッション', 'メンズ'],
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'condition_name' => '目立った傷や汚れなし',
                'image_path' => 'items/hdd.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => null,
                'description' => '新鮮な玉ねぎ3束のセット',
                'condition_name' => 'やや傷や汚れあり',
                'image_path' => 'items/onions.jpg',
                'category_names' => ['キッチン'],
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => null,
                'description' => 'クラシックなデザインの革靴',
                'condition_name' => '状態が悪い',
                'image_path' => 'items/shoes.jpg',
                'category_names' => ['ファッション', 'メンズ'],
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => null,
                'description' => '高性能なノートパソコン',
                'condition_name' => '良好',
                'image_path' => 'items/laptop.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => null,
                'description' => '高音質のレコーディング用マイク',
                'condition_name' => '目立った傷や汚れなし',
                'image_path' => 'items/mic.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => null,
                'description' => 'おしゃれなショルダーバッグ',
                'condition_name' => 'やや傷や汚れあり',
                'image_path' => 'items/bag.jpg',
                'category_names' => ['ファッション', 'レディース'],
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => null,
                'description' => '使いやすいタンブラー',
                'condition_name' => '状態が悪い',
                'image_path' => 'items/tumbler.jpg',
                'category_names' => ['キッチン'],
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'condition_name' => '良好',
                'image_path' => 'items/grinder.jpg',
                'category_names' => ['キッチン', '家電'],
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => null,
                'description' => '便利なメイクアップセット',
                'condition_name' => '目立った傷や汚れなし',
                'image_path' => 'items/makeup.jpg',
                'category_names' => ['コスメ'],
            ],
        ];

        foreach ($items as $item) {
            $conditionId = $conditionMap[$item['condition_name']] ?? null;

            if ($conditionId === null) {
                continue;
            }

            $itemId = DB::table('items')->insertGetId([
                'user_id' => $sellerId,
                'condition_id' => $conditionId,
                'name' => $item['name'],
                'brand' => $item['brand'],
                'description' => $item['description'],
                'price' => $item['price'],
                'image_path' => $item['image_path'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($item['category_names'] as $categoryName) {
                $categoryId = $categoryMap[$categoryName] ?? null;

                if ($categoryId === null) {
                    continue;
                }

                DB::table('category_item')->insert([
                    'item_id' => $itemId,
                    'category_id' => $categoryId,
                ]);
            }
        }
    }
}
