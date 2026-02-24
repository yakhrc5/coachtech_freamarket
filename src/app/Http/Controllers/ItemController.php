<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $tab = $request->input('tab', 'recommend');

        $query = Item::query()
            ->with('purchase') // SOLD判定のため
            ->latest();

        if (Auth::check()) {
            $query->where('user_id', '!=', Auth::id());
        }

        // キーワード検索（商品名・ブランド）
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('brand', 'like', '%' . $keyword . '%');
            });
        }

        // マイリスト（いいねした商品）
        if ($tab === 'mylist') {
            if (!Auth::check()) {
                // 未ログインなら空の一覧にする（仕様次第でloginへリダイレクトでもOK）
                $query->whereRaw('1 = 0');
            } else {
                $userId = Auth::id();
                $query->whereHas('likes', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            }
        }

        $items = $query->paginate(12)->appends($request->query());

        return view('items.index', compact('items'));
    }

    public function show(int $itemId)
    {
        $item = Item::query()
            ->with([
                'categories',          // 複数カテゴリ表示（要件2）
                'condition',           // 商品状態表示
                'comments.user' => fn($q) => $q->latest(),       // コメントしたユーザー情報
            ])
            ->withCount([
                'likes',               // いいね数
                'comments',            // コメント数
            ])
            ->findOrFail($itemId);

        $isLiked = false;
        if (auth()->check()) {
            // likesの実装が belongsToMany(User::class,'likes') の想定
            $isLiked = $item->likes()->where('users.id', auth()->id())->exists();
        }

        return view('items.show', compact('item', 'isLiked'));
    }
}
