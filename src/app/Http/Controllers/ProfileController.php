<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        // ログイン中のユーザー情報を取得する
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // プロフィール編集画面を表示する
        return view('mypage.profile', compact('user'));
    }

    public function update(ProfileRequest $request)
    {
        // 更新対象のログイン中ユーザーを取得する
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // バリデーション済みデータを取得する
        $data = $request->validated();

        // users テーブルのカラム形式に合わせて更新データを整える
        $updateData = [
            'name' => $data['name'],
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'building' => $data['building'] ?? null,
        ];

        // プロフィール画像が送信されている場合のみ画像を更新する
        if ($request->hasFile('profile_image')) {
            // 既存のプロフィール画像があれば削除する
            if (!empty($user->profile_image_path)) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            // 新しい画像を storage/app/public/profiles に保存する
            $path = $request->file('profile_image')->store('profiles', 'public');

            // 保存した画像パスを更新データに追加する
            $updateData['profile_image_path'] = $path;
        }

        // ユーザー情報を更新する
        $user->update($updateData);

        // マイページから来た場合はマイページに戻す
        if ($request->input('from') === 'mypage') {
            return redirect()->route('mypage.show');
        }

        // それ以外は商品一覧へ戻す
        return redirect()->route('items.index');
    }
}
