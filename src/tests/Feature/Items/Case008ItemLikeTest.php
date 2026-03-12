<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Condition;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Case008 いいね機能
 *
 * 対応要件:
 * - いいねアイコンを押下することによって、いいねした商品として登録することができる。
 * - 追加済みのアイコンは色が変化する
 * - 再度いいねアイコンを押下することによって、いいねを解除することができる。
 */
class Case008ItemLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_count_is_displayed_as_increased(): void
    {
        // テスト用データを準備する
        $data = $this->prepareLikeData();

        // いいねするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // いいね前の商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // いいね前はいいね数が0件で表示されることを確認する
        $response->assertSee('<p class="product-detail__stat-count">0</p>', false);

        // いいねアイコンを押下する
        $response = $this->post(
            route('items.like.toggle', ['item_id' => $data['item']->id])
        );

        // いいね処理後にリダイレクトされることを確認する
        $response->assertRedirect();

        // likesテーブルにいいねが登録されていることを確認する
        $this->assertDatabaseHas('likes', [
            'user_id' => $data['loginUser']->id,
            'item_id' => $data['item']->id,
        ]);

        // いいね後の商品詳細画面を再度開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // いいね後はいいね数が1件で表示されることを確認する
        $response->assertSee('<p class="product-detail__stat-count">1</p>', false);
    }

    public function test_liked_icon_changes_color(): void
    {
        // テスト用データを準備する
        $data = $this->prepareLikeData();

        // いいねするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // いいね前の商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // いいね前は未いいねアイコンが表示されることを確認する
        $response->assertSee('heart-default.png');

        // いいねアイコンを押下する
        $response = $this->post(
            route('items.like.toggle', ['item_id' => $data['item']->id])
        );

        // いいね処理後にリダイレクトされることを確認する
        $response->assertRedirect();

        // likesテーブルにいいねが登録されていることを確認する
        $this->assertDatabaseHas('likes', [
            'user_id' => $data['loginUser']->id,
            'item_id' => $data['item']->id,
        ]);

        // いいね後の商品詳細画面を再度開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // いいね後はいいね済みアイコンが表示されることを確認する
        $response->assertSee('heart-liked.png');
    }

public function test_user_can_unlike_item(): void
    {
        // テスト用データを準備する
        $data = $this->prepareLikeData();

        // いいねするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // 先にいいねを登録する
        $this->post(
            route('items.like.toggle', ['item_id' => $data['item']->id])
        );

        // likesテーブルにいいねが登録されていることを確認する
        $this->assertDatabaseHas('likes', [
            'user_id' => $data['loginUser']->id,
            'item_id' => $data['item']->id,
        ]);

        // 再度いいねアイコンを押して解除する
        $response = $this->post(
            route('items.like.toggle', ['item_id' => $data['item']->id])
        );

        // 解除処理後にリダイレクトされることを確認する
        $response->assertRedirect();

        // likesテーブルからいいねが削除されていることを確認する
        $this->assertDatabaseMissing('likes', [
            'user_id' => $data['loginUser']->id,
            'item_id' => $data['item']->id,
        ]);

        // 解除後の商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // 解除後は未いいねアイコンが表示されることを確認する
        $response->assertSee('heart-default.png');

        // 解除後はいいね数が0件で表示されることを確認する
        $response->assertSee('<p class="product-detail__stat-count">0</p>', false);
    }

    /**
     * 商品詳細テスト用データを準備する
     *
     * @return array{
     *  loginUser: \App\Models\User,
     *  seller: \App\Models\User,
     *  item: \App\Models\Item
     * }
     */
    private function prepareLikeData(): array
    {

        // マスタをseedしてから、商品を作成して必要な情報を紐付ける
        $this->seed([
            ConditionsSeeder::class,
            CategoriesSeeder::class,
        ]);
        // loginUserを作成
        $loginUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        // 出品者を作成
        $seller = User::factory()->create();

        // 状態、カテゴリは最初の1件を取得
        $condition = Condition::query()->firstOrFail();
        $category = Category::query()->firstOrFail();

        $item = Item::query()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price' => 12345,
            'image_path' => 'items/test-item.jpg',
        ]);

        // カテゴリを紐付け
        $item->categories()->attach($category->id);

        return [
            'item' => $item,
            'seller' => $seller,
            'loginUser' => $loginUser,
        ];
    }
}
