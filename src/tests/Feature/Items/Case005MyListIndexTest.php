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
 * Case005 マイリスト一覧取得
 *
 * 対応要件:
 * - いいねした商品だけが表示される
 * - 購入済み商品は「Sold」と表示される
 * - 未認証の場合は何も表示されない
 */

class Case005MyListIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_mylist_displays_only_liked_items(): void
    {
        $data = $this->prepareMyListData();

        $response = $this->actingAs($data['user'])
            ->get(route('items.index', ['tab' => 'mylist']));

        $response->assertStatus(200);

        // いいねした商品は表示
        $response->assertSeeText($data['likedItem']->name);

        // いいねしていない商品は非表示
        $response->assertDontSeeText($data['notLikedItem']->name);
    }

    public function test_purchased_item_in_mylist_is_displayed_with_sold_label(): void
    {
        $data = $this->prepareSoldMyListData();

        $response = $this->actingAs($data['user'])
            ->get(route('items.index', ['tab' => 'mylist']));

        $response->assertStatus(200);
        $response->assertSeeText($data['soldLikedItem']->name);

        // Bladeの表示ロジックで「Sold」が表示されることを確認
        $response->assertSeeText('Sold');
    }

    public function test_guest_sees_nothing_on_mylist(): void
    {
        // ゲストでも商品データは存在する状態を作る（＝tab=mylist だけ空になることを確認）
        $this->seedBaseData();

        $response = $this->get(route('items.index', ['tab' => 'mylist']));

        $response->assertStatus(200);

        // Seederの代表商品名が表示されないことを確認
        $response->assertDontSeeText('腕時計');
        $response->assertDontSeeText('HDD');

        // items が空になっていることも確認
        $response->assertViewHas('items', function ($items) {
            return $items->count() === 0;
        });
    }

    /**
     * 基本データ（Seeder）を投入
     */
    private function seedBaseData(): void
    {
        $this->seed([
            CategoriesSeeder::class,
            ConditionsSeeder::class,
            PaymentMethodsSeeder::class,
            UsersSeeder::class,
            ItemsSeeder::class,
        ]);
    }

    /**
     * いいね表示確認用データ
     *
     * @return array{
     *   user:\App\Models\User,
     *   likedItem:\App\Models\Item,
     *   notLikedItem:\App\Models\Item
     * }
     */
    private function prepareMyListData(): array
    {
        $this->seedBaseData();

        $user = User::factory()->create();

        // ItemsSeederの固定10商品から使う
        $likedItem = Item::query()->where('name', '腕時計')->firstOrFail();
        $notLikedItem = Item::query()->where('name', 'HDD')->firstOrFail();

        // ログインユーザーが「腕時計」にいいね
        DB::table('likes')->insert([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $user,
            'likedItem' => $likedItem,
            'notLikedItem' => $notLikedItem,
        ];
    }

    /**
     * Sold表示確認用データ
     *
     * @return array{
     *   user:\App\Models\User,
     *   soldLikedItem:\App\Models\Item
     * }
     */
    private function prepareSoldMyListData(): array
    {
        $data = $this->prepareMyListData();

        $paymentMethodId = DB::table('payment_methods')->value('id');

        DB::table('purchases')->insert([
            'user_id' => $data['user']->id,            // 購入者
            'item_id' => $data['likedItem']->id,       // UNIQUE制約あり
            'payment_method_id' => $paymentMethodId,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区1-2-3',
            'building' => 'テストビル101',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $data['user'],
            'soldLikedItem' => $data['likedItem'],
        ];
    }
}
