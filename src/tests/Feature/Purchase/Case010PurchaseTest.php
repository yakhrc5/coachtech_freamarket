<?php

namespace Tests\Feature\Purchase;

use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Case010 商品購入機能
 *
 * 対応要件:
 * - 「購入する」ボタンを押下すると購入が完了する
 * - 購入した商品は商品一覧画面にて「Sold」と表示される
 * - 「プロフィール/購入した商品一覧」に追加されている
 */
class Case010PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_purchase_item(): void
    {
        // 購入機能テスト用データを準備する
        $data = $this->preparePurchaseData();

        /** @var \App\Models\User $buyer */
        $buyer = $data['buyer'];

        /** @var \App\Models\Item $item */
        $item = $data['item'];

        // Stripeで使用する支払い方法を1件取得する
        /** @var \App\Models\PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::query()
            ->whereNotNull('stripe_code')
            ->firstOrFail();

        // Stripeの外部通信をモックする
        $this->mockStripeCheckoutSession();

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // 購入画面を開く
        $response = $this->get(route('purchase.show', ['item_id' => $item->id]));

        // 購入画面が正常に表示されることを確認する
        $response->assertOk();

        // 「購入する」ボタンを押下して購入処理を実行する
        $response = $this->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // Stripeの決済画面URLへリダイレクトされることを確認する
        $response->assertRedirect('https://checkout.stripe.test/session');

        // purchasesテーブルに購入情報が登録されていることを確認する
        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_method_id' => $paymentMethod->id,
            'postal_code' => '111-1111',
            'address' => '東京都港区初期町1-1',
            'building' => '初期ビル',
        ]);
    }

    public function test_purchased_item_is_displayed_as_sold_on_item_index(): void
    {
        // 購入機能テスト用データを準備する
        $data = $this->preparePurchaseData();

        /** @var \App\Models\User $buyer */
        $buyer = $data['buyer'];

        /** @var \App\Models\Item $item */
        $item = $data['item'];

        // 購入処理を実行する
        $this->purchaseItem($buyer, $item);

        // 商品一覧画面を開く
        $response = $this->get(route('items.index'));

        // 商品一覧画面が正常に表示されることを確認する
        $response->assertOk();

        // 購入した商品名が表示されていることを確認する
        $response->assertSeeText($item->name);

        // 購入した商品にSoldが表示されることを確認する
        $response->assertSeeText('Sold');
    }

    public function test_purchased_item_is_displayed_on_my_page_buy_list(): void
    {
        // 購入機能テスト用データを準備する
        $data = $this->preparePurchaseData();

        /** @var \App\Models\User $buyer */
        $buyer = $data['buyer'];

        /** @var \App\Models\Item $item */
        $item = $data['item'];

        // 購入処理を実行する
        $this->purchaseItem($buyer, $item);

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // マイページの購入一覧画面を開く
        $response = $this->get(route('mypage.show', ['page' => 'buy']));

        // 購入一覧画面が正常に表示されることを確認する
        $response->assertOk();

        // 購入した商品が購入一覧に表示されることを確認する
        $response->assertSeeText($item->name);
    }

    /**
     * 購入機能テスト用データを準備する
     *
     * @return array{
     *   buyer: \App\Models\User,
     *   item: \App\Models\Item
     * }
     */
    private function preparePurchaseData(): array
    {
        // マスタデータと指定商品データを投入する
        $this->seed([
            UsersSeeder::class,
            ConditionsSeeder::class,
            CategoriesSeeder::class,
            PaymentMethodsSeeder::class,
            ItemsSeeder::class,
        ]);

        // 購入対象商品を取得する
        /** @var \App\Models\Item $item */
        $item = Item::query()->where('name', 'HDD')->firstOrFail();

        // 購入者ユーザーを作成する
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 購入者の配送先住所を設定する
        $buyer->forceFill([
            'postal_code' => '111-1111',
            'address' => '東京都港区初期町1-1',
            'building' => '初期ビル',
        ])->save();

        // 購入対象商品が自分の商品にならないよう調整する
        if ((int) $item->user_id === (int) $buyer->id) {
            /** @var \App\Models\User $otherSeller */
            $otherSeller = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            $item->update([
                'user_id' => $otherSeller->id,
            ]);
        }

        return [
            'buyer' => $buyer,
            'item' => $item,
        ];
    }

    /**
     * 商品購入処理を共通化する
     */
    private function purchaseItem(User $buyer, Item $item): void
    {
        // Stripeで使用する支払い方法を1件取得する
        /** @var \App\Models\PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::query()
            ->whereNotNull('stripe_code')
            ->firstOrFail();

        // Stripeの外部通信をモックする
        $this->mockStripeCheckoutSession();

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // 購入処理を実行する
        $response = $this->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // Stripeの決済画面URLへリダイレクトされることを確認する
        $response->assertRedirect('https://checkout.stripe.test/session');

        // purchasesテーブルに購入情報が登録されていることを確認する
        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
    }

    /**
     * Stripe Checkout Session の作成処理をモックする
     */
    private function mockStripeCheckoutSession(): void
    {
        Mockery::mock('alias:Stripe\Checkout\Session')
            ->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'url' => 'https://checkout.stripe.test/session',
            ]);
    }
}
