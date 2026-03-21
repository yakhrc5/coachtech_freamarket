<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;

class PurchaseAddressController extends Controller
{
    public function edit($item_id)
    {
        // ログイン中のユーザーを取得
        // ※ auth + verified ミドルウェアで保護している前提
        $user = auth()->user();

        // 商品を取得（存在しなければ404）
        $item = Item::findOrFail($item_id);

        // すでに購入済みなら一覧へ戻す
        if ($this->isPurchased($item)) {
            return redirect()->route('items.index');
        }

        // 初期値は「住所変更セッション」→「プロフィール住所」の順で採用
        $address = $this->resolveAddress($item->id, $user);

        // 住所変更画面を表示
        return view('purchase.address', compact('item', 'address'));
    }

    public function update(AddressRequest $request, $item_id): RedirectResponse
    {
        // ログイン中のユーザーを取得
        $user = $request->user();

        // 商品を取得（存在しなければ404）
        $item = Item::findOrFail($item_id);

        // すでに購入済みなら一覧へ戻す
        if ($this->isPurchased($item)) {
            return redirect()->route('items.index');
        }

        // バリデーション済みデータを取得
        $data = $request->validated();

        // 購入画面に戻ったときに反映できるよう、商品ごとに配送先をセッション保存
        session()->put($this->shippingSessionKey($item->id), [
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'building' => $data['building'] ?? null,
        ]);

        // 購入画面へ戻す
        return redirect()->route('purchase.show', ['item_id' => $item->id]);
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
    private function resolveAddress(int $itemId, $user): array
    {
        // この商品の住所変更セッションを取得
        $sessionAddress = session()->get($this->shippingSessionKey($itemId));

        // セッション側に郵便番号・住所があればそちらを優先
        if (
            is_array($sessionAddress) &&
            !empty($sessionAddress['postal_code']) &&
            !empty($sessionAddress['address'])
        ) {
            return [
                'postal_code' => $sessionAddress['postal_code'],
                'address' => $sessionAddress['address'],
                'building' => $sessionAddress['building'] ?? null,
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
