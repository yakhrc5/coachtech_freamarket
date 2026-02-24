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
        $user = Auth::user();
        $page = $request->input('page', 'sell');

        $sellItems = Item::where('user_id', $user->id)
            ->with('purchase')
            ->latest()
            ->get();

        $buyPurchases = Purchase::where('user_id', $user->id)
            ->with('item.purchase')
            ->latest()
            ->get();

        return view('mypage.mypage', compact('user', 'page', 'sellItems', 'buyPurchases'));
    }
}
