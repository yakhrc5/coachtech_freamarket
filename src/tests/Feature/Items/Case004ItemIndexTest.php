<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Case004 商品一覧取得
 *
 * 対応要件:
 * - 全商品を取得できる
 * - 購入済み商品は「Sold」と表示される
 * - 自分が出品した商品は表示されない
 */
class Case004ItemIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_show_all_items(): void
    {
        // テスト用データを準備（出品商品 + 購入済み商品を含む）
        $data = $this->prepareItemData();

        // itemテーブルの全件数を取得（ゲスト時の一覧表示対象 = 全商品）
        $allItemCount = Item::query()->count();

        // ゲスト状態でトップページを開く
        $indexResponse = $this->get(route('items.index'));

        // 正常に表示されることを確認
        $indexResponse->assertStatus(200);

        // トップページに表示する商品一覧の件数が、itemテーブル全件数と一致することを確認
        $this->assertIndexItemsCount($indexResponse, $allItemCount);

        // 商品一覧に全商品が表示されることを確認（漏れチェック）
        foreach ($data['allItems'] as $item) {
            $indexResponse->assertSeeText($item->name);
        }
    }

    public function test_index_page_show_sold_label_for_purchased_item(): void
    {
        // テスト用データを準備（購入済み商品を含む）
        $data = $this->prepareItemData();

        // ゲスト状態でトップページを開く
        $indexResponse = $this->get(route('items.index'));

        // 正常に表示されることを確認
        $indexResponse->assertStatus(200);

        // 購入済み商品の商品名が表示されることを確認
        $indexResponse->assertSeeText($data['buyItem']->name);

        // 購入済み商品に「Sold」ラベルが表示されることを確認
        $indexResponse->assertSeeText('Sold');
    }

    public function test_index_page_not_show_my_items_when_logged_in(): void
    {
        // テスト用データを準備（ログインユーザーの出品商品を含む）
        $data = $this->prepareItemData();

        // ログインユーザー以外の商品件数（一覧に表示されるべき件数）を取得
        $visibleItemCount = Item::query()
            ->where('user_id', '!=', $data['user']->id)
            ->count();

        // ログイン状態でトップページを開く
        $indexResponse = $this->actingAs($data['user'])->get(route('items.index'));

        // 正常に表示されることを確認
        $indexResponse->assertStatus(200);

        // トップページに表示する商品一覧の件数が、ログインユーザー以外の商品件数と一致することを確認
        $this->assertIndexItemsCount($indexResponse, $visibleItemCount);

        // ログインユーザー本人の出品商品が表示されないことを確認
        foreach ($data['myItems'] as $myItem) {
            $indexResponse->assertDontSeeText($myItem->name);
        }

        // 他ユーザーの商品は表示されることを確認
        $indexResponse->assertSeeText($data['buyItem']->name);
    }

    /**
 * @return array{
 *   user:\App\Models\User,
 *   sellItem:\App\Models\Item,
 *   buyItem:\App\Models\Item,
 *   allItems:\Illuminate\Support\Collection<int,\App\Models\Item>,
 *   myItems:\Illuminate\Support\Collection<int,\App\Models\Item>
 * }
 */
    private function prepareItemData(): array
    {

        // マスタ + 指定10商品を投入
        $this->seed(UsersSeeder::class);
        $this->seed(ConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        // プロフィール画像を用意（Storage::url()でアクセスできる場所に）
        Storage::disk('public')->put('profiles/test-user.png', 'dummy');

        //ItemsSeederが固定商品を作るので商品名で取得する
        /** @var \App\Models\Item $sellItem */
        $sellItem = Item::query()->where('name', '腕時計')->firstOrFail();
        /** @var \App\Models\Item $buyItem */
        $buyItem = Item::query()->where('name', 'HDD')->firstOrFail();

        // 商品の出品者を取得（UsersSeederで作成された先頭ユーザーを使う）
        /** @var \App\Models\User $user */
        $user = User::query()->findOrFail($sellItem->user_id);

        // verifiedルートのため email_verified_at を付与
        $user->forceFill([
            'name' => 'テスト太郎',
            'profile_image_path' => 'profiles/test-user.png',
            'email_verified_at' => now(),
        ])->save();

        // 購入商品を「他人の商品」にしておく（自然な状態）
        /** @var \App\Models\User $otherSeller */
        $otherSeller = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $buyItem->update([
            'user_id' => $otherSeller->id,
        ]);

        // payment_methods はSeeder済みなので1件取得
        $paymentMethodId = DB::table('payment_methods')->value('id');

        // 購入履歴作成（PurchaseFactoryを使用）
        Purchase::factory()->create([
            'user_id' => $user->id,      // 購入者
            'item_id' => $buyItem->id,   // 購入商品
            'payment_method_id' => $paymentMethodId,
        ]);

        // 期待値確認用の一覧を取得
        $allItems = Item::query()->get();
        $myItems = Item::query()
            ->where('user_id', $user->id)
            ->get();

        return [
            'user' => $user,
            'sellItem' => $sellItem,
            'buyItem' => $buyItem,
            'allItems' => $allItems,
            'myItems' => $myItems,
        ];
    }

    /**
     * トップページに表示する商品一覧の件数を確認する
     *
     * @param \Illuminate\Testing\TestResponse $response
     */
    private function assertIndexItemsCount($response, int $expectedCount): void
    {
        // Controllerでviewに渡している商品一覧(items) の件数を確認する
        // ※ items.index のview変数名が 'items' でない場合はここを変更する
        $response->assertViewHas('items', function ($items) use ($expectedCount) {
            return $this->resolveDisplayedItemCountFromViewData($items) === $expectedCount;
        });
    }

    /**
     * viewに渡された商品一覧データから、画面表示件数を取得する
     *
     * @param mixed $items
     */
    private function resolveDisplayedItemCountFromViewData($items): int
    {
        // Collection / Paginator など、count() が使えるもの
        if (is_object($items) && method_exists($items, 'count')) {
            return (int) $items->count();
        }

        // array の場合
        if (is_countable($items)) {
            return count($items);
        }

        return 0;
    }

}
