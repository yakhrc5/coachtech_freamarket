<?php

namespace Tests\Feature\Sell;

use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Case015 出品商品情報登録
 * - 商品出品画面にて必要な情報が保存できること
 *  （カテゴリ、商品の状態、商品名、ブランド名、商品の説明、販売価格）
 */
class Case015SellCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_item_on_sell_page_and_data_is_saved(): void
    {
        /** @var \App\Models\User $user */
        $user = $this->prepareSellCreateData();

        // ログイン状態にする（verified済みユーザー）
        $this->actingAs($user);

        // 1. 出品ページの表示確認
        $sellResponse = $this->get(route('sell.create'));
        $sellResponse->assertStatus(200);

        $categoryId = DB::table('categories')->value('id');
        $conditionId = DB::table('conditions')->value('id');

        // 2. 出品データ
        $imagePath = database_path('seeders/images/items/watch.jpg');
        $sellData = [
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'テスト商品の説明',
            'price' => 5000,
            'condition_id' => $conditionId,
            'category_ids' => [$categoryId],
            'image' => new UploadedFile(
                $imagePath,
                'watch.jpg',
                'image/jpeg',
                null,
                true // test mode
                ),
        ];

        // 3. 出品実行
        $createResponse = $this->post(route('sell.store'), $sellData);

        // 4. データベースに出品データが保存されていることを確認
        $this->assertDatabaseHas('items', [
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'テスト商品の説明',
            'price' => 5000,
            'condition_id' => $conditionId,
            'user_id' => $user->id,
        ]);
        // 作成された商品を取得
        $createdItem = DB::table('items')->where('name', 'テスト商品')->first();
        $this->assertNotNull($createdItem);

        // 5. 画像pathが保存されていること
        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $publicDisk->assertExists($createdItem->image_path);

        // 6. 中間テーブルにカテゴリ紐づけが保存されていること
        $this->assertDatabaseHas('category_item', [
            'item_id' => $createdItem->id,
            'category_id' => $categoryId,
        ]);

        // 7. リダイレクト先を厳密確認(マイページ)
        $createResponse->assertRedirect(route('mypage.show'));

        $mypageResponse = $this->get(route('mypage.show', ['page' => 'sell'])); // 実装に合わせて
        $mypageResponse->assertStatus(200);
        $mypageResponse->assertSeeText('テスト商品');
    }

    private function prepareSellCreateData(): User
    {
        // マスタ
        $this->seed(ConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);

        // 画像アップロードの保存先をテスト用に差し替え（publicディスク）
        Storage::fake('public');

        // ユーザーを取得
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(), // sellは verified 必須ルート
        ]);

        return $user;
    }
}
