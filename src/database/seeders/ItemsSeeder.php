<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
        $categoryMap  = DB::table('categories')->pluck('id', 'name');

        // 画像コピー元（Git管理する場所）
        $sourceDir = database_path('seeders/images/items');

        // 画像コピー先（公開ディスク：storage/app/public/items）
        Storage::disk('public')->makeDirectory('items');

        $items = [
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'condition_name' => '良好',
                'image_file' => 'watch.jpg',
                'category_names' => ['ファッション', 'メンズ'],
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'condition_name' => '目立った傷や汚れなし',
                'image_file' => 'hdd.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => null,
                'description' => '新鮮な玉ねぎ3束のセット',
                'condition_name' => 'やや傷や汚れあり',
                'image_file' => 'onions.jpg',
                'category_names' => ['キッチン'],
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => null,
                'description' => 'クラシックなデザインの革靴',
                'condition_name' => '状態が悪い',
                'image_file' => 'shoes.jpg',
                'category_names' => ['ファッション', 'メンズ'],
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => null,
                'description' => '高性能なノートパソコン',
                'condition_name' => '良好',
                'image_file' => 'laptop.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => null,
                'description' => '高音質のレコーディング用マイク',
                'condition_name' => '目立った傷や汚れなし',
                'image_file' => 'mic.jpg',
                'category_names' => ['家電'],
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => null,
                'description' => 'おしゃれなショルダーバッグ',
                'condition_name' => 'やや傷や汚れあり',
                'image_file' => 'bag.jpg',
                'category_names' => ['ファッション', 'レディース'],
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => null,
                'description' => '使いやすいタンブラー',
                'condition_name' => '状態が悪い',
                'image_file' => 'tumbler.jpg',
                'category_names' => ['キッチン'],
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'condition_name' => '良好',
                'image_file' => 'grinder.jpg',
                'category_names' => ['キッチン', '家電'],
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => null,
                'description' => '便利なメイクアップセット',
                'condition_name' => '目立った傷や汚れなし',
                'image_file' => 'makeup.jpg',
                'category_names' => ['コスメ'],
            ],
        ];

        DB::transaction(function () use ($items, $sellerId, $now, $conditionMap, $categoryMap, $sourceDir) {
            foreach ($items as $item) {
                // condition が見つからないなら登録しない
                $conditionId = $conditionMap[$item['condition_name']] ?? null;
                if ($conditionId === null) {
                    continue;
                }

                // ========= 画像コピー =========
                // コピー元: database/seeders/images/items/{file}
                $srcPath = $sourceDir . DIRECTORY_SEPARATOR . $item['image_file'];

                // 画像が無い場合は止める
                if (!File::exists($srcPath)) {
                    throw new \RuntimeException('Seed image not found: ' . $srcPath);
                }

                // コピー先(=DBに入れるパス): items/{file}
                $imagePath = 'items/' . $item['image_file'];

                // 既にコピー済みなら再コピーしない
                if (!Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->put($imagePath, File::get($srcPath));
                }

                // ========= items 登録 =========
                $itemId = DB::table('items')->insertGetId([
                    'user_id' => $sellerId,
                    'condition_id' => $conditionId,
                    'name' => $item['name'],
                    'brand' => $item['brand'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'image_path' => $imagePath, // DBは常に items/xxx.jpg 形式
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // ========= category_item 登録 =========
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
        });
    }
}
