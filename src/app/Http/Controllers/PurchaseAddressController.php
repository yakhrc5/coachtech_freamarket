<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PurchaseAddressController extends Controller
{
    public function edit(Item $item)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // すでに購入済みなら一覧へ（多重購入防止）
        if ($this->isPurchased($item)) {
            return redirect('/')->with('message', 'この商品は購入済みです。');
        }

        // 初期値：セッションに変更住所があれば優先、なければプロフィール
        $address = $this->resolveAddress($item->id, $user);

        // ✅ あなたの希望パス：resources/views/purchase/address.blade.php
        return view('purchase.address', compact('item', 'address'));
    }

    public function update(AddressRequest $request, Item $item): RedirectResponse
    {
        $user = $request->user();

        // すでに購入済みなら一覧へ
        if ($this->isPurchased($item)) {
            return redirect('/')->with('message', 'この商品は購入済みです。');
        }

        $data = $request->validated();

        // 購入画面に戻ったときに反映できるよう、商品ごとにセッション保存
        session()->put($this->shippingSessionKey($item->id), [
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'building' => $data['building'] ?? null,
        ]);

        return redirect()
            ->route('purchase.show', $item)
            ->with('message', '配送先住所を更新しました。');
    }

    private function isPurchased(Item $item): bool
    {
        return Purchase::query()
            ->where('item_id', $item->id)
            ->exists();
    }

    private function resolveAddress(int $itemId, $user): array
    {
        $sessionAddress = session()->get($this->shippingSessionKey($itemId));

        if (is_array($sessionAddress) && !empty($sessionAddress['postal_code']) && !empty($sessionAddress['address'])) {
            return [
                'postal_code' => $sessionAddress['postal_code'],
                'address' => $sessionAddress['address'],
                'building' => $sessionAddress['building'] ?? null,
            ];
        }

        // プロフィール（Userに住所カラムがある想定）
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
