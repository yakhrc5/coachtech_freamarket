<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Case006 商品検索機能
 * - 「商品名」で部分一致検索ができる
 * - 検索状態がマイリストでも保持されている
 */
class Case006ItemSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_items_by_partial_name(): void
    {
        // Arrange: 検索確認用のテストデータを準備する
        $testData = $this->prepareItemSearchTestData();

        // Act: 商品名の部分一致キーワードで検索する
        $response = $this->get(route('items.index', [
            'keyword' => '時計',
        ]));

        // Assert: 正常に表示されることを確認する
        $response->assertStatus(200);

        // Assert: 部分一致する商品が表示されることを確認する
        $response->assertSeeText($testData['matchedItem1']->name);
        $response->assertSeeText($testData['matchedItem2']->name);

        // Assert: 部分一致しない商品が表示されないことを確認する
        $response->assertDontSeeText($testData['unmatchedItem']->name);
    }

    public function test_search_keyword_is_kept_on_mylist_tab(): void
    {
        // Arrange: マイリスト検索確認用のテストデータを準備する
        $testData = $this->prepareMyListSearchTestData();
        $loginUser = $testData['user'];

        // Act: マイリストタブでキーワード検索する
        $response = $this->actingAs($loginUser)->get(route('items.index', [
            'tab' => 'mylist',
            'keyword' => '時計',
        ]));

        // Assert: 正常に表示されることを確認する
        $response->assertStatus(200);

        // Assert: 検索入力欄にキーワードが保持されていることを確認する
        // ※ Bladeの検索inputが value="{{ request('keyword') }}" 前提
        $response->assertSee('value="時計"', false);

        // Assert: マイリスト内でも検索結果が絞り込まれていることを確認する
        $response->assertSeeText($testData['matchedLikedItem']->name);
        $response->assertDontSeeText($testData['unmatchedLikedItem']->name);
    }

    /**
     * @return array{
     *   matchedItem1:\App\Models\Item,
     *   matchedItem2:\App\Models\Item,
     *   unmatchedItem:\App\Models\Item
     * }
     */
    private function prepareItemSearchTestData(): array
    {
        // 1. マスタ + 指定商品を投入する
        $this->seed(UsersSeeder::class);
        $this->seed(ConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        // 2. 検索用に使う商品を3件取得する（Seeder投入済みデータを流用）
        $items = Item::query()->orderBy('id')->take(3)->get()->values();

        if ($items->count() < 3) {
            $this->fail('Case006: 検索テストに必要な商品数（3件）が不足しています。ItemsSeederを確認してください。');
        }

        /** @var \App\Models\Item $matchedItem1 */
        $matchedItem1 = $items[0];

        /** @var \App\Models\Item $matchedItem2 */
        $matchedItem2 = $items[1];

        /** @var \App\Models\Item $unmatchedItem */
        $unmatchedItem = $items[2];

        // 3. 商品名を検索用に分かりやすく更新する
        $matchedItem1->update(['name' => 'Armani メンズ時計']);
        $matchedItem2->update(['name' => '壁掛け時計']);
        $unmatchedItem->update(['name' => 'レザーバッグ']);

        // 4. テストで使う値を返す
        return [
            'matchedItem1' => $matchedItem1->fresh(),
            'matchedItem2' => $matchedItem2->fresh(),
            'unmatchedItem' => $unmatchedItem->fresh(),
        ];
    }

    /**
     * @return array{
     *   user:\App\Models\User,
     *   matchedLikedItem:\App\Models\Item,
     *   unmatchedLikedItem:\App\Models\Item
     * }
     */
    private function prepareMyListSearchTestData(): array
    {
        // 1. マスタ + 指定商品を投入する
        $this->seed(UsersSeeder::class);
        $this->seed(ConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        // 2. ログインユーザー（verified）を作成する
        /** @var \App\Models\User $loginUser */
        $loginUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. 他人の出品者ユーザーを作成する（自分の商品除外に引っかからないようにする）
        /** @var \App\Models\User $otherSeller */
        $otherSeller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 4. マイリスト検索用に使う商品を2件取得する（Seeder投入済みデータを流用）
        $items = Item::query()->orderBy('id')->take(2)->get()->values();

        if ($items->count() < 2) {
            $this->fail('Case006: マイリスト検索テストに必要な商品数（2件）が不足しています。ItemsSeederを確認してください。');
        }

        /** @var \App\Models\Item $matchedLikedItem */
        $matchedLikedItem = $items[0];

        /** @var \App\Models\Item $unmatchedLikedItem */
        $unmatchedLikedItem = $items[1];

        // 5. どちらも他人の商品にして、商品名を検索用に更新する
        $matchedLikedItem->update([
            'user_id' => $otherSeller->id,
            'name' => 'いいね済みの時計',
        ]);

        $unmatchedLikedItem->update([
            'user_id' => $otherSeller->id,
            'name' => 'いいね済みのバッグ',
        ]);

        // 6. いいねを付与する（両方ともマイリスト対象）
        DB::table('likes')->insert([
            [
                'user_id' => $loginUser->id,
                'item_id' => $matchedLikedItem->id,
            ],
            [
                'user_id' => $loginUser->id,
                'item_id' => $unmatchedLikedItem->id,
            ],
        ]);

        // 7. テストで使う値を返す
        return [
            'user' => $loginUser,
            'matchedLikedItem' => $matchedLikedItem->fresh(),
            'unmatchedLikedItem' => $unmatchedLikedItem->fresh(),
        ];
    }
}
