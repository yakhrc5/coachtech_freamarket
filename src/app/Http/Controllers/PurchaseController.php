<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            return redirect('/')->with('message', 'この商品は購入済みです。');
        }

        // 配送先：セッションに変更住所があればそれを優先、なければプロフィール
        $shipping = $this->resolveShipping($item, $user);

        $paymentMethods = PaymentMethod::orderBy('id')->get();

        return view('purchase.show', compact('item', 'shipping', 'paymentMethods'));
    }

    public function store(PurchaseRequest $request, Item $item): RedirectResponse
    {
        $user = $request->user();

        if ((int) $item->user_id === (int) $user->id) {
            return redirect()
                ->route('purchase.show', $item)
                ->withErrors(['purchase' => '自分の商品は購入できません。']);
        }

        if ($this->isPurchased($item)) {
            return redirect('/')->with('message', 'この商品は購入済みです。');
        }

        // 支払い方法だけバリデーション
        $data = $request->validated();

        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        // 配送先：セッション優先 → プロフィール
        $shipping = $this->resolveShipping($item, $user);

        // もし万一プロフィール住所が空なら弾く
        if (empty($shipping['postal_code']) || empty($shipping['address'])) {
            return redirect()
                ->route('purchase.show', $item)
                ->withErrors(['address' => '配送先住所が未登録です。プロフィールを確認してください。']);
        }

        // コンビニ払いは「支払う」クリック時点で在庫確保（sold扱い）にする
        if ($paymentMethod->name === 'コンビニ支払い') {
            if (!Purchase::where('item_id', $item->id)->exists()) {
                Purchase::create([
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'payment_method_id' => $data['payment_method_id'],
                    'postal_code' => $shipping['postal_code'],
                    'address' => $shipping['address'],
                    'building' => $shipping['building'] ?? null,
                ]);
            }

            // 住所変更セッションを掃除（次回購入時にプロフィール住所を優先させるため）
            session()->forget("purchase.shipping.item_{$item->id}");
        }
        // 支払い方法 → Stripeのpayment_method_types
        $paymentMethodTypes = $data['payment_method_id'] === 1
            ? ['konbini']
            : ['card'];

        Stripe::setApiKey(config('services.stripe.secret'));

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

            // success確定（/stripe/success）で purchases 作成するために必要な情報を metadata に詰める
            'metadata' => [
                'user_id' => (string) $user->id,
                'item_id' => (string) $item->id,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_name' => $paymentMethod->name,
                'postal_code' => $shipping['postal_code'] ?? '',
                'address' => $shipping['address'] ?? '',
                'building' => $shipping['building'] ?? '',
            ],

            // successは一覧へ
            'success_url' => url('/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
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