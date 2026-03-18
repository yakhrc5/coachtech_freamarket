<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class ItemLikeController extends Controller
{
    public function toggle(int $itemId)
    {
        $item = Item::findOrFail($itemId);

        $item->likes()->toggle(auth()->id()); // 付与/解除を自動で切替

        return back();
    }
}
