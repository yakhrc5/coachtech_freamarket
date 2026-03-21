<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Case003 ログアウト機能
 *
 * 対応要件:
 * - ログアウトができる
 */
class Case003LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout(): void
    {
        // ユーザー作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // ログイン状態にする
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        // ログアウト実行（Fortify/標準はPOST /logout）
        $response = $this->post(route('logout'));

        // ログアウトできていることを確認
        $response->assertRedirect(); // リダイレクトされること
        $this->assertGuest();         // 未ログイン状態になっていること
    }
}
