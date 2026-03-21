<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    public function show(Request $request)
    {
        // ログイン中のユーザーを取得する
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 表示するタブを取得する
        // 未指定の場合は出品一覧(sell)を初期表示にする
        $page = $request->input('page', 'sell');

        // 自分が出品した商品一覧を取得する
        // purchase を一緒に読み込んでおくことで、Blade 側で Sold 判定しやすくする
        $sellItems = Item::query()
            ->where('user_id', $user->id)
            ->with('purchase')
            ->latest()
            ->get();

        // 自分が購入した商品の購入履歴を取得する
        // item と、その item の purchase も読み込んで表示しやすくする
        $buyPurchases = Purchase::query()
            ->where('user_id', $user->id)
            ->with('item.purchase')
            ->latest()
            ->get();

        // マイページを表示する
        return view('mypage.mypage', compact('user', 'page', 'sellItems', 'buyPurchases'));
    }
}
