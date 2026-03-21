<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class ItemLikeController extends Controller
{
    public function toggle(int $itemId)
    {
        // 指定された商品を取得する
        $item = Item::findOrFail($itemId);

        // likes の中間テーブルに対して、
        // ログイン中ユーザーのいいねを付与 / 解除で自動切り替えする
        $item->likes()->toggle(Auth::id());

        // 元の画面へ戻る
        return back();
    }
}