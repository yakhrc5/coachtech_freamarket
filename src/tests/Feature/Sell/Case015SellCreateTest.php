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
 *
 * 対応要件:
 * - 商品出品画面にて必要な情報が保存できること
 *   （カテゴリ、商品の状態、商品名、ブランド名、商品の説明、販売価格）
 */
class Case015SellCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_item_on_sell_page_and_data_is_saved(): void
    {
        /** @var \App\Models\User $user */
        $user = $this->prepareSellCreateData();

        // 出品者ユーザーでログインする（verified済み）
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        // 出品ページを開く
        $sellResponse = $this->get(route('sell.create'));

        // 出品ページが正常に表示されることを確認する
        $sellResponse->assertOk();

        // 登録に使用するカテゴリIDと状態IDを取得する
        $categoryId = DB::table('categories')->value('id');
        $conditionId = DB::table('conditions')->value('id');

        // 出品データを用意する
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

        // 出品処理を実行する
        $createResponse = $this->post(route('sell.store'), $sellData);

        // itemsテーブルに出品データが保存されていることを確認する
        $this->assertDatabaseHas('items', [
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'テスト商品の説明',
            'price' => 5000,
            'condition_id' => $conditionId,
            'user_id' => $user->id,
        ]);

        // 作成された商品を取得する
        $createdItem = DB::table('items')
            ->where('name', 'テスト商品')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($createdItem);

        // publicディスクに画像ファイルが保存されていることを確認する
        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $publicDisk->assertExists($createdItem->image_path);

        // 中間テーブルにカテゴリ紐付けが保存されていることを確認する
        $this->assertDatabaseHas('category_item', [
            'item_id' => $createdItem->id,
            'category_id' => $categoryId,
        ]);

        // 出品後にマイページへリダイレクトされることを確認する
        $createResponse->assertRedirect(route('mypage.show'));

        // マイページの出品一覧で、作成した商品が表示されることを確認する
        $mypageResponse = $this->get(route('mypage.show', ['page' => 'sell']));
        $mypageResponse->assertOk();
        $mypageResponse->assertSeeText('テスト商品');
    }

    /**
     * 出品機能テスト用データを準備する
     */
    private function prepareSellCreateData(): User
    {
        // マスタデータを投入する
        $this->seed([
            ConditionsSeeder::class,
            CategoriesSeeder::class,
        ]);

        // 画像アップロードの保存先をテスト用に差し替える
        Storage::fake('public');

        // 出品者ユーザーを作成する
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        return $user;
    }
}