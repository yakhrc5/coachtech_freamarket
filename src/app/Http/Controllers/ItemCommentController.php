<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ItemCommentController extends Controller
{
    public function store(CommentRequest $request, int $itemId): RedirectResponse
    {
        // バリデーション済みのコメント内容と、
        // ログイン中ユーザー・対象商品IDを使ってコメントを登録する
        Comment::create([
            'item_id' => $itemId,
            'user_id' => Auth::id(),
            'body' => $request->input('body'),
        ]);

        // 元の画面（商品詳細）へ戻る
        return back();
    }
}
