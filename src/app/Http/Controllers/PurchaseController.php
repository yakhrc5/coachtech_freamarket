<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;


class PurchaseController extends Controller
{
    public function show(Item $item)
    {
        $user = Auth::user();

        // 念のため（通常はauthミドルウェアで守られてる想定）
        if (!$user) {
            abort(403);
        }

        // すでに購入済みなら一覧へ（多重購入防止）
        if ($this->isPurchased($item)) {
            return redirect('/');
        }

        // 配送先：セッションに変更住所があればそれを優先、なければプロフィール
        $shipping = $this->resolveShipping($item, $user);

        $paymentMethods = PaymentMethod::orderBy('id')->get();

        return view('purchase.show', compact('item', 'shipping', 'paymentMethods'));
    }

    public function store(PurchaseRequest $request, Item $item): RedirectResponse
    {
        $user = $request->user();

        // 購入者と出品者のIDチェ//
        if ((int) $item->user_id === (int) $user->id) {
            return redirect()
                ->route('purchase.show', $item);
        }
        // 売り切れチェック
        if ($this->isPurchased($item)) {
            return redirect('/');
        }

        // 支払い方法だけバリデーション
        $data = $request->validated();

        // 支払い方法マスタをDBから取得（存在しないIDが送られてきた場合は例外で404）
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        // 配送先：セッション優先 → プロフィール
        $shipping = $this->resolveShipping($item, $user);

        // プロフィール住所が空なら弾く
        if (empty($shipping['postal_code']) || empty($shipping['address'])) {
            return redirect()
                ->route('purchase.show', $item);
        }

        // 支払い方法取得
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        // 「購入する」クリック時点で在庫確保（sold扱い）にする
        if (!Purchase::where('item_id', $item->id)->exists()) {
            Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'payment_method_id' => $data['payment_method_id'],
                'postal_code' => $shipping['postal_code'],
                'address' => $shipping['address'],
                'building' => $shipping['building'] ?? null,
            ]);

        // 住所変更セッションを掃除（次回購入時にプロフィール住所を優先させるため）
        session()->forget("purchase.shipping.item_{$item->id}");
        }

        // Stripeに渡す支払い方法
        $paymentMethodTypes = [$paymentMethod->stripe_code];

        // Stripeキーを設定
        Stripe::setApiKey(config('services.stripe.secret'));

        // Stripe決済画面に遷移するためのCheckoutSessionを作成
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
            // 購入レコードは store() 時点で作成済み。
            // Stripe決済画面や成功画面側で参照できるように、購入情報・配送先を metadata に保持
            'metadata' => [
                'user_id' => (string) $user->id,
                'item_id' => (string) $item->id,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_name' => $paymentMethod->name,
                'postal_code' => $shipping['postal_code'] ?? '',
                'address' => $shipping['address'] ?? '',
                'building' => $shipping['building'] ?? '',
            ],

            // 決済結果の確認・完了表示用の戻り先
            'success_url' => route('stripe.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('purchase.show', $item),
        ]);

        return redirect()->away($session->url);
    }

    private function isPurchased(Item $item): bool
    {
        return Purchase::query()
            ->where('item_id', $item->id)
            ->exists();
    }

    private function resolveShipping(Item $item, $user): array
    {
        $sessionShipping = session()->get($this->shippingSessionKey($item->id));

        if (is_array($sessionShipping) && !empty($sessionShipping['postal_code']) && !empty($sessionShipping['address'])) {
            return [
                'postal_code' => $sessionShipping['postal_code'],
                'address' => $sessionShipping['address'],
                'building' => $sessionShipping['building'] ?? null,
            ];
        }

        // プロフィール
        return [
            'postal_code' => $user->postal_code ?? '',
            'address' => $user->address ?? '',
            'building' => $user->building ?? null,
        ];
    }

    private function shippingSessionKey(int $itemId): string
    {
        return "purchase.shipping.item_{$itemId}";
    }
}