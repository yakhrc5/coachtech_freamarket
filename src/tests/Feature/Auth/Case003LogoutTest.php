<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Case003 ログアウト機能
 * - ログアウトができる
 */
class Case003LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout(): void
    {
        // 1. ユーザー作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 2. ログイン状態にする
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        // 3. ログアウト実行（Fortify/標準はPOST /logout）
        $response = $this->post(route('logout'));

        // 4. ログアウトできていることを確認
        $response->assertStatus(302); // リダイレクトされること
        $this->assertGuest();         // 未ログイン状態になっていること
    }
}
