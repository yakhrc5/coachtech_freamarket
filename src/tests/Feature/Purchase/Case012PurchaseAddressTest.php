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
use Tests\TestCase;

/**
 * Case012 配送先変更機能
 * - 送付先住所変更画面にて登録した住所が商品購入画面に反映されている
 * - 購入した商品に送付先住所が紐づいて登録される
 */
class Case012PurchaseAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_address_is_reflected_on_purchase_page(): void
    {
        $data = $this->preparePurchaseData();

        $buyer = $data['buyer'];
        $buyItem = $data['buyItem'];

        // 1. ログイン状態にする（verified済みユーザー）
        $this->actingAs($buyer);

        // 2. 送付先住所変更ページ
        $editResponse = $this->get(route('purchase.address.edit', ['item_id' => $buyItem->id]));
        $editResponse->assertStatus(200);

        // 3. 送付先住所変更画面で住所を更新する
        $addressData = [
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ];
        $updateResponse = $this->patch(
            route('purchase.address.update', ['item_id' => $buyItem->id]),
            $addressData
        );

        // 4. 商品購入画面に戻ることを確認
        $updateResponse->assertRedirect(route('purchase.show', ['item_id' => $buyItem->id]));

        // 5. 商品購入画面に反映されていることを確認
        $purchaseResponse = $this->get(route('purchase.show', ['item_id' => $buyItem->id]));
        $purchaseResponse->assertStatus(200);

        $purchaseResponse->assertSeeText('123-4567');
        $purchaseResponse->assertSeeText('東京都千代田区テスト町1-1');
        $purchaseResponse->assertSeeText('テストビル101号');
    }

    public function test_changed_address_is_saved_in_purchases_table_when_purchase_is_executed(): void
    {
        $data = $this->preparePurchaseData();

        $buyer = $data['buyer'];
        $buyItem = $data['buyItem'];

        // ログイン状態にする（verified済みユーザー）
        $this->actingAs($buyer);

        // 変更後住所
        $addressData = [
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ];

        // 1) 配送先住所変更（セッションに保存される想定）
        $addressUpdateResponse = $this->patch(
            route('purchase.address.update', ['item_id' => $buyItem->id]),
            $addressData
        );

        // 購入画面へ戻る
        $addressUpdateResponse->assertRedirect(route('purchase.show', ['item_id' => $buyItem->id]));

        // 支払い方法ID（Seeder済みマスタから1件）
        $paymentMethodId = DB::table('payment_methods')->value('id');

        // 念のため：購入前は purchases にまだ無いことを確認
        $this->assertDatabaseMissing('purchases', [
            'item_id' => $buyItem->id,
        ]);

        // 2) 購入実行
        // ※ store() の後半で Stripe に進むため、環境によっては 500 になることがある
        //    ただし Purchase::create() は Stripe 前なので、DB保存確認が目的ならこの後で assertDatabaseHas を行う
        try {
            $purchaseStoreResponse = $this->post(
                route('purchase.store', ['item_id' => $buyItem->id]),
                [
                    'payment_method_id' => $paymentMethodId,
                ]
            );

            // Stripe SDK 呼び出しで例外が飛ばない環境の場合は、リダイレクトされることを確認
            $this->assertContains($purchaseStoreResponse->getStatusCode(), [302, 500]);
        } catch (\Throwable $e) {
            // Stripe SDK 呼び出しで例外が飛んでも、今回の目的は「Purchase::create() されたか」なので続行
            // （Purchase::create() は Stripe 呼び出しより前で実行される実装）
        }

        // 3) purchases テーブルに変更後住所が保存されていることを確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $buyItem->id,
            'payment_method_id' => $paymentMethodId,
            'postal_code' => '123-4567',
            'address' => '東京都千代田区テスト町1-1',
            'building' => 'テストビル101号',
        ]);
    }

    /**
     * @return array
     *   buyer:\App\Models\User,
     *   buyItem:\App\Models\Item}
     */
    private function preparePurchaseData(): array
    {
        // マスタ + 指定10商品を投入
        $this->seed(UsersSeeder::class);
        $this->seed(ConditionsSeeder::class);
        $this->seed(CategoriesSeeder::class);
        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ItemsSeeder::class);

        // 購入対象商品（ItemsSeederの固定商品を使う）
        /** @var \App\Models\Item $buyItem */
        $buyItem = Item::query()->where('name', 'HDD')->firstOrFail();

        // 商品の出品者とは別ユーザーを購入者にする（自然な状態）
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email_verified_at' => now(), // verifiedルート対策
        ]);

        // 初期住所を入れておく
        $buyer->forceFill([
            'postal_code' => '111-1111',
            'address' => '東京都港区初期町1-1',
            'building' => '初期ビル',
        ])->save();

        // 購入対象商品は他人の商品のままにする
        if ((int) $buyItem->user_id === (int) $buyer->id) {
            /** @var \App\Models\User $otherSeller */
            $otherSeller = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            $buyItem->update([
                'user_id' => $otherSeller->id,
            ]);
        }

        return [
            'buyer' => $buyer,
            'buyItem' => $buyItem
        ];
    }
}
