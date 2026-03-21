<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;

class PurchaseController extends Controller
{
    public function show($item_id)
    {
        // ログイン中のユーザーを取得
        $user = auth()->user();

        // 商品を取得（存在しなければ404）
        $item = Item::findOrFail($item_id);

        // すでに購入済みの商品は購入画面に入れない
        if ($this->isPurchased($item)) {
            return redirect()->route('items.index');
        }

        // 配送先は「住所変更セッション」→「プロフィール住所」の順で採用
        $shipping = $this->resolveShipping($item, $user);

        // 支払い方法一覧を取得
        $paymentMethods = PaymentMethod::orderBy('id')->get();

        // 購入画面を表示
        return view('purchase.show', compact('item', 'shipping', 'paymentMethods'));
    }

    public function store(PurchaseRequest $request, $item_id): RedirectResponse
    {
        // ログイン中のユーザーを取得
        $user = $request->user();

        // 購入対象の商品を取得
        $item = Item::findOrFail($item_id);

        // 自分が出品した商品は購入できない
        if ((int) $item->user_id === (int) $user->id) {
            return redirect()->route('items.index');
        }

        // すでに購入済みなら一覧へ戻す
        if ($this->isPurchased($item)) {
            return redirect()->route('items.index');
        }

        // バリデーション済みデータを取得
        $data = $request->validated();

        // 選択された支払い方法を取得
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        // 配送先は「住所変更セッション」→「プロフィール住所」の順で採用
        $shipping = $this->resolveShipping($item, $user);

        // 配送先の必須情報が空なら購入画面へ戻す
        if (empty($shipping['postal_code']) || empty($shipping['address'])) {
            return redirect()->route('purchase.show', ['item_id' => $item->id]);
        }

        // まだ購入レコードが無い場合のみ作成する
        // ※ 1商品につき1購入のため、二重登録を防ぐ
        if (!$this->isPurchased($item)) {
            Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'payment_method_id' => $data['payment_method_id'],
                'postal_code' => $shipping['postal_code'],
                'address' => $shipping['address'],
                'building' => $shipping['building'] ?? null,
            ]);

            // この商品の住所変更セッションを削除
            session()->forget($this->shippingSessionKey($item->id));
        }

        // Stripeに渡す支払い方法コードを配列化
        $paymentMethodTypes = [$paymentMethod->stripe_code];

        // Stripeのシークレットキーを設定
        Stripe::setApiKey(config('services.stripe.secret'));

        // Stripe Checkout Session を作成
        $session = CheckoutSession::create([
            'mode' => 'payment',
            'payment_method_types' => $paymentMethodTypes,
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item->name,
                    ],
                    'unit_amount' => (int) $item->price,
                ],
                'quantity' => 1,
            ]],

            // 決済結果画面などで参照しやすいように購入情報を保持
            'metadata' => [
                'user_id' => (string) $user->id,
                'item_id' => (string) $item->id,
                'payment_method_id' => (string) $paymentMethod->id,
                'payment_method_name' => $paymentMethod->name,
                'postal_code' => $shipping['postal_code'] ?? '',
                'address' => $shipping['address'] ?? '',
                'building' => $shipping['building'] ?? '',
            ],

            // 決済成功時の戻り先
            'success_url' => route('stripe.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),

            // 決済キャンセル時の戻り先
            'cancel_url' => route('purchase.show', ['item_id' => $item->id]),
        ]);

        // Stripe決済画面へリダイレクト
        return redirect()->away($session->url);
    }

    /**
     * 商品がすでに購入済みか判定
     */
    private function isPurchased(Item $item): bool
    {
        return Purchase::query()
            ->where('item_id', $item->id)
            ->exists();
    }

    /**
     * 配送先を決定
     * 優先順位:
     * 1. この商品の住所変更セッション
     * 2. ユーザーのプロフィール住所
     */
    private function resolveShipping(Item $item, $user): array
    {
        // セッションに一時保存された配送先を取得
        $sessionShipping = session()->get($this->shippingSessionKey($item->id));

        // セッション側に郵便番号・住所が入っていればそちらを優先
        if (
            is_array($sessionShipping) &&
            !empty($sessionShipping['postal_code']) &&
            !empty($sessionShipping['address'])
        ) {
            return [
                'postal_code' => $sessionShipping['postal_code'],
                'address' => $sessionShipping['address'],
                'building' => $sessionShipping['building'] ?? null,
            ];
        }

        // なければプロフィール住所を使う
        return [
            'postal_code' => $user->postal_code ?? '',
            'address' => $user->address ?? '',
            'building' => $user->building ?? null,
        ];
    }

    /**
     * 商品ごとの配送先セッションキーを返す
     */
    private function shippingSessionKey(int $itemId): string
    {
        return "purchase.shipping.item_{$itemId}";
    }
}
