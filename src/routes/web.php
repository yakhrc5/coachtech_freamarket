<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseAddressController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemLikeController;
use App\Http\Controllers\ItemCommentController;
use App\Http\Controllers\StripeSuccessController;

/*
|--------------------------------------------------------------------------
| Public (guest OK)
|--------------------------------------------------------------------------
*/

// PG01 / PG02: 商品一覧
Route::get('/', [ItemController::class, 'index'])->name('items.index');

// PG05: 商品詳細
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show');


/*
|--------------------------------------------------------------------------
| Auth only (verified じゃなくてOK)
|--------------------------------------------------------------------------
| ※ verification.notice は未認証ユーザーも見る必要があるので verified は付けない
*/
Route::middleware('auth')->group(function () {
    // 認証誘導画面を差し替え
    Route::get('/email/verify', function () {
        // 認証完了後にプロフィールへ遷移させるため、意図的に intended を上書き
        session(['url.intended' => route('profile.edit')]);

        return view('auth.verify-email');
    })->name('verification.notice');

});


/*
|--------------------------------------------------------------------------
| Protected (login + verified required)
|--------------------------------------------------------------------------
| ※ “認証が必要なアクション” は全てここにまとめる
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // PG08: 出品
    Route::get('/sell', [SellController::class, 'create'])->name('sell.create');
    Route::post('/sell', [SellController::class, 'store'])->name('sell.store');

    // PG05: 商品詳細のアクションのうち、ログインユーザーのみができるアクション
    Route::post('/item/{item_id}/like', [ItemLikeController::class, 'toggle'])->name('items.like.toggle');
    Route::post('/item/{item_id}/comments', [ItemCommentController::class, 'store'])->name('items.comments.store');

    // PG06: 購入
    Route::get('/purchase/{item}', [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/purchase/{item}', [PurchaseController::class, 'store'])->name('purchase.store');

    // PG07: 送付先住所変更
    Route::get('/purchase/address/{item}', [PurchaseAddressController::class, 'edit'])->name('purchase.address.edit');
    Route::patch('/purchase/address/{item}', [PurchaseAddressController::class, 'update'])->name('purchase.address.update');

    // PG09 / PG11 / PG12: マイページ
    Route::get('/mypage', [MypageController::class, 'show'])->name('mypage.show');

    // PG10: プロフィール編集
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/stripe/success', [StripeSuccessController::class, 'success'])->name('stripe.success');
});