<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        // 検索欄に入力されたキーワードを取得
        $keyword = $request->input('keyword');

        // タブを取得（未指定ならおすすめ）
        $tab = $request->input('tab', 'recommend');

        // 商品一覧のベースとなるクエリを作成
        // purchase を一緒に取得しておくことで、Blade 側で SOLD 判定しやすくする
        // latest() は created_at の新しい順に並べる
        $query = Item::query()
            ->with('purchase')
            ->latest();

        // キーワードが入力されている場合のみ検索条件を追加する
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                // 商品名 または ブランド名 に部分一致する商品を絞り込む
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('brand', 'like', '%' . $keyword . '%');
            });
        }

        // マイリストタブの処理
        if ($tab === 'mylist') {
            // 未ログイン時は何も表示しない
            if (!Auth::check()) {
                $query->whereRaw('1 = 0');
            } else {
                // ログイン中は、自分がいいねした商品のみに絞り込む
                $userId = Auth::id();

                $query->whereHas('likes', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            }
        } else {
            // おすすめタブのときだけ、
            // ログイン中なら自分が出品した商品を一覧から除外する
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
        }

        // 商品一覧を取得する
        $items = $query->get();

        // 商品一覧画面を表示
        return view('items.index', compact('items'));
    }

    public function show(int $itemId)
    {
        // 指定されたIDの商品を取得する
        $item = Item::query()
            ->with([
                'purchase',                    // 購入情報（SOLD判定のため）
                'categories',                  // 商品カテゴリ
                'condition',                   // 商品状態
                'comments' => fn($q) => $q->latest(), // コメントを新しい順で取得
                'comments.user',               // コメント投稿者のユーザー情報
            ])
            ->withCount([
                'likes',                       // いいね数
                'comments',                    // コメント数
            ])
            ->findOrFail($itemId);

        // ゲストでも view に渡せるように初期値を先に入れておく
        $isLiked = false;
        $isOwnItem = false;

        // ログイン中なら、いいね状態と自分の商品かどうかを判定する
        if (Auth::check()) {
            $isLiked = $item->likes()
                ->where('users.id', Auth::id())
                ->exists();

            // 商品の出品者IDと、ログイン中ユーザーIDが一致すれば自分の商品
            $isOwnItem = $item->user_id === Auth::id();
        }

        // 商品詳細画面を表示
        return view('items.show', compact('item', 'isLiked', 'isOwnItem'));
    }
}
