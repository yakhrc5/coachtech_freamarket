<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return view('mypage.profile', compact('user'));
    }

    public function update(ProfileRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $data = $request->validated();

        // DBカラム形に整形
        $updateData = [
            'name' => $data['name'],
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'building' => $data['building'] ?? null,
        ];

        // 画像（任意）
        if ($request->hasFile('profile_image')) {
            // 古い画像を削除
            if (!empty($user->profile_image_path)) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $path = $request->file('profile_image')->store('profiles', 'public');
            $updateData['profile_image_path'] = $path;
        }

        $user->update($updateData);

        if ($request->input('from') === 'mypage') {
            return redirect()->route('mypage.show')->with('success', 'プロフィールを更新しました');
        }

        return redirect()->route('items.index')->with('success', 'プロフィールを更新しました');
    }
}
