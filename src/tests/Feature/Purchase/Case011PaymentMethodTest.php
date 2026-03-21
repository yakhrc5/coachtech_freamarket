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
use Tests\TestCase;

/**
 * Case011 支払い方法選択機能
 *
 * 対応要件:
 * - 小計画面で変更が反映される
 * - 選択した支払い方法が正しく反映される
 */
class Case011PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_payment_method_is_reflected_on_purchase_page(): void
    {
        // テスト用データを準備する
        $data = $this->preparePaymentMethodData();

        $buyer = $data['buyer'];
        $buyItem = $data['buyItem'];
        $paymentMethod = $data['paymentMethod'];

        // 購入者でログインする
        $this->actingAs($buyer);
        $this->assertAuthenticatedAs($buyer);

        // 支払い方法選択後の状態を old input で再現して購入ページを開く
        $response = $this->withSession([
            '_old_input' => [
                'payment_method_id' => (string) $paymentMethod->id,
            ],
        ])->get(route('purchase.show', ['item_id' => $buyItem->id]));

        // 購入ページが正常に表示されることを確認する
        $response->assertOk();

        // hidden input に選択した支払い方法IDが保持されていることを確認する
        $response->assertSee(
            'name="payment_method_id"',
            false
        );
        $response->assertSee(
            'value="' . $paymentMethod->id . '"',
            false
        );

        // 小計欄の支払い方法表示に選択した支払い方法名が反映されていることを確認する
        $this->assertMatchesRegularExpression(
            '/id="paymentMethodPreview"[^>]*>\s*' . preg_quote($paymentMethod->name, '/') . '\s*<\/p>/',
            $response->getContent()
        );
    }

    /**
     * 支払い方法選択機能テスト用データを準備する
     *
     * @return array{
     *   buyer: \App\Models\User,
     *   buyItem: \App\Models\Item,
     *   paymentMethod: \App\Models\PaymentMethod
     * }
     */
    private function preparePaymentMethodData(): array
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
        $buyItem = Item::query()->where('name', 'HDD')->firstOrFail();

        // 購入者ユーザーを作成する
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
        if ((int) $buyItem->user_id === (int) $buyer->id) {
            $otherSeller = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            $buyItem->update([
                'user_id' => $otherSeller->id,
            ]);
        }

        // 支払い方法を1件取得する
        $paymentMethod = PaymentMethod::query()->firstOrFail();

        // テストで使用するデータを返す
        return [
            'buyer' => $buyer,
            'buyItem' => $buyItem,
            'paymentMethod' => $paymentMethod,
        ];
    }
}
