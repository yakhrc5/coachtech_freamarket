<?php

namespace Tests\Feature\MyPage;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Case014 ユーザー情報更新
 *
 * 対応要件:
 * - 変更項目が初期値として過去設定されていること
 *  （プロフィール画像、ユーザー名、郵便番号、住所）
 */
class Case014ProfileUpdateMyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profilepage_shows_profile_image_user_name_postal_code_address_building(): void
    {
        $user = $this->prepareMyPageData();

        // ログイン状態にする（verified済みユーザー）
        $this->actingAs($user);

        // プロフィール編集ページを開く
        $response = $this->get(route('profile.edit'));

        // ページが正常に表示されることを確認する
        $response->assertOk();

        // 初期値として各情報が表示されていることを確認する
        $profileResponse = $this->get(route('profile.edit'));
        $profileResponse->assertOk();
        $profileResponse->assertSee(Storage::url($user->profile_image_path), false);
        $profileResponse->assertSee('value="' . $user->name . '"', false);
        $profileResponse->assertSee('value="' . $user->postal_code . '"', false);
        $profileResponse->assertSee('value="' . $user->address . '"', false);
        $profileResponse->assertSee('value="' . $user->building . '"', false);
    }

    private function prepareMyPageData(): User
    {
        // ユーザー作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // プロフィール画像を用意（Storage::url()でアクセスできる場所に）
        Storage::disk('public')->put('profiles/test-user.png', 'dummy');

        // verifiedルートのため email_verified_at を付与
        $user->forceFill([
        'name' => 'テスト太郎',
        'postal_code' => '123-4567',
        'address' => '東京都千代田区テスト町1-1',
        'building' => 'テストビル101号',
        'profile_image_path' => 'profiles/test-user.png',
        'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}