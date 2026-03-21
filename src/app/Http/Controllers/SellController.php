<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use App\Models\Category;
use App\Models\Condition;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellController extends Controller
{
    public function create()
    {
        // カテゴリー一覧をID順で取得する
        $categories = Category::query()->orderBy('id')->get();

        // 商品状態一覧をID順で取得する
        $conditions = Condition::query()->orderBy('id')->get();

        // 出品画面を表示する
        return view('sell.create', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        // バリデーション済みデータを取得する
        $data = $request->validated();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 商品登録とカテゴリ紐づけを1つの処理としてまとめる
        return DB::transaction(function () use ($request, $data, $user) {
            // 画像を storage/app/public/items に保存する
            // DBには items/ファイル名 の形式で保存される
            $imagePath = $request->file('image')->store('items', 'public');

            // 商品情報を登録する
            $item = Item::create([
                'user_id' => $user->id,
                'condition_id' => $data['condition_id'],
                'name' => $data['name'],
                'brand' => $data['brand'] ?? null,
                'description' => $data['description'],
                'price' => $data['price'],
                'image_path' => $imagePath,
            ]);

            // 中間テーブル category_item にカテゴリを紐づける
            // 複数選択された category_ids をまとめて保存する
            $item->categories()->sync($data['category_ids']);

            // 出品完了後、マイページへリダイレクトする
            return redirect()
                ->route('mypage.show');
        });
    }
}
