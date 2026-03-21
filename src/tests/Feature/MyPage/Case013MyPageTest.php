<?php

namespace Tests\Feature\MyPage;

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
 * Case013 ユーザー情報取得
 *
 * 対応要件:
 * - 必要な情報が取得できる
 *  （プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
 */
class Case013MyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_mypage_shows_profile_image_user_name_and_sell_buy_items(): void
    {
        $data = $this->prepareMyPageData();
        $user = $data['user'];
        $sellItem = $data['sellItem'];
        $buyItem = $data['buyItem'];

        // ログイン状態にする（verified済みユーザー）
        $this->actingAs($user);

        // プロフィールページ（基本表示）
        $profileResponse = $this->get(route('mypage.show'));
        $profileResponse->assertOk();
        $profileResponse->assertSeeText($user->name);
        $profileResponse->assertSee(Storage::url($user->profile_image_path), false);

        // 出品した商品一覧
        $sellResponse = $this->get(route('mypage.show', ['page' => 'sell']));
        $sellResponse->assertOk();
        $sellResponse->assertSeeText($sellItem->name);
        $sellResponse->assertSee(Storage::url($sellItem->image_path), false);

        // 購入した商品一覧
        $buyResponse = $this->get(route('mypage.show', ['page' => 'buy']));
        $buyResponse->assertOk();
        $buyResponse->assertSeeText($buyItem->name);
        $buyResponse->assertSee(Storage::url($buyItem->image_path), false);
    }

    /**
     * @return array{
     *   user:\App\Models\User,
     *   sellItem:\App\Models\Item,
     *   buyItem:\App\Models\Item
     * }
     */
    private function prepareMyPageData(): array
    {
        // マスタ + 指定10商品を投入
        $this->seed([
            UsersSeeder::class,
            ConditionsSeeder::class,
            CategoriesSeeder::class,
            PaymentMethodsSeeder::class,
            ItemsSeeder::class,
        ]);

        // プロフィール画像を用意する
        Storage::disk('public')->put('profiles/test-user.png', 'dummy');

        //ItemsSeederが固定商品を作るので商品名で取得する
        /** @var \App\Models\Item $sellItem */
        $sellItem = Item::query()->where('name', '腕時計')->firstOrFail();
        /** @var \App\Models\Item $buyItem */
        $buyItem = Item::query()->where('name', 'HDD')->firstOrFail();

        // 商品の出品者を取得（UsersSeederで作成された先頭ユーザーを使う）
        /** @var \App\Models\User $user */
        $user = User::query()->findOrFail($sellItem->user_id);

        // 初期値を設定し、verified状態にする
        $user->forceFill([
            'name' => 'テスト太郎',
            'profile_image_path' => 'profiles/test-user.png',
            'email_verified_at' => now(),
        ])->save();

        // 購入商品を「他人の商品」にしておく
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

        return [
            'user' => $user,
            'sellItem' => $sellItem,
            'buyItem' => $buyItem,
        ];
    }
}
