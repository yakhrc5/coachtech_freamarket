<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class ItemCommentController extends Controller
{
    public function store(CommentRequest $request, int $itemId): RedirectResponse
    {
        Comment::create([
            'item_id' => $itemId,
            'user_id' => auth()->id(),
            'body' => $request->input('body'),
        ]);

        return back();
    }
}
