<?php

namespace Tests\Feature\Purchase;

use App\Models\Item;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Case012 配送先変更機能
 *
 * 対応要件:
 * - 送付先住所変更画面にて登録した住所が商品購入画面に反映されている
 * - 購入した商品に送付先住所が紐づいて登録される
 */
class Case012PurchaseAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_address_is_reflected_on_purchase_page(): void
    {
        // 配送先変更機能テスト用データを準備する
        $data = $this->preparePurchaseData();

        /** @var \App\Models\User $buyer */
        $buyer = $data['buyer'];

        /** @var \App\Models\Item $item */
        $item = $data['item'];

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // 送付先住所変更画面を開く
        $editResponse = $this->get(route('purchase.address.edit', ['item_id' => $item->id]));

        // 住所変更画面が正常に表示されることを確認する
        $editResponse->assertOk();

        // 変更後の住所データを用意する
        $addressData = [
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ];

        // 送付先住所を更新する
        $updateResponse = $this->patch(
            route('purchase.address.update', ['item_id' => $item->id]),
            $addressData
        );

        // 更新後に商品購入画面へ戻ることを確認する
        $updateResponse->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // 商品購入画面を再度開く
        $purchaseResponse = $this->get(route('purchase.show', ['item_id' => $item->id]));

        // 商品購入画面が正常に表示されることを確認する
        $purchaseResponse->assertOk();

        // 変更した住所が商品購入画面に表示されていることを確認する
        $purchaseResponse->assertSeeText('123-4567');
        $purchaseResponse->assertSeeText('東京都千代田区テスト町1-1');
        $purchaseResponse->assertSeeText('テストビル101号');
    }

    public function test_changed_address_is_saved_in_purchases_table_when_purchase_is_executed(): void
    {
        // 配送先変更機能テスト用データを準備する
        $data = $this->preparePurchaseData();

        /** @var \App\Models\User $buyer */
        $buyer = $data['buyer'];

        /** @var \App\Models\Item $item */
        $item = $data['item'];

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // 変更後の住所データを用意する
        $addressData = [
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ];

        // 先に配送先住所を変更する
        $addressUpdateResponse = $this->patch(
            route('purchase.address.update', ['item_id' => $item->id]),
            $addressData
        );

        // 更新後に商品購入画面へ戻ることを確認する
        $addressUpdateResponse->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // Stripeで使用する支払い方法IDを1件取得する
        $paymentMethodId = DB::table('payment_methods')
            ->whereNotNull('stripe_code')
            ->value('id');

        // 購入前は purchases テーブルにまだ登録されていないことを確認する
        $this->assertDatabaseMissing('purchases', [
            'item_id' => $item->id,
        ]);

        // Stripeの外部通信をモックする
        $this->mockStripeCheckoutSession();

        // 購入処理を実行する
        $purchaseStoreResponse = $this->post(
            route('purchase.store', ['item_id' => $item->id]),
            [
                'payment_method_id' => $paymentMethodId,
            ]
        );

        // Stripeの決済画面URLへリダイレクトされることを確認する
        $purchaseStoreResponse->assertRedirect('https://checkout.stripe.test/session');

        // purchases テーブルに変更後住所が保存されていることを確認する
        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_method_id' => $paymentMethodId,
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ]);
    }

    /**
     * 配送先変更機能テスト用データを準備する
     *
     * @return array{
     *   buyer:\App\Models\User,
     *   item:\App\Models\Item
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

        // 購入者の初期住所を設定する
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
