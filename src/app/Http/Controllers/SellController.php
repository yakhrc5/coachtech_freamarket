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
        $categories = Category::query()->orderBy('id')->get();
        $conditions = Condition::query()->orderBy('id')->get();

        return view('sell.create', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        $data = $request->validated();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($request, $data, $user) {
            // 画像は storage/app/public/items に保存（DBは items/xxx.jpg 形式）
            $imagePath = $request->file('image')->store('items', 'public');

            $item = Item::create([
                'user_id'       => $user->id,
                'condition_id'  => $data['condition_id'],
                'name'          => $data['name'],
                'brand'         => $data['brand'] ?? null,
                'description'   => $data['description'],
                'price'         => $data['price'],
                'image_path'    => $imagePath,
            ]);

            // カテゴリー（複数）
            $item->categories()->sync($data['category_ids']);

            return redirect()
                ->route('mypage.show');
        });
    }
}
