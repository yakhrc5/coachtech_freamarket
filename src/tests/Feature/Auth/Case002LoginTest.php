<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Case002 ログイン機能
 *
 * 対応要件:
 * - メールアドレスが入力されていない場合、バリデーションメッセージが表示される
 * - パスワードが入力されていない場合、バリデーションメッセージが表示される
 * - 入力情報が間違っている場合、バリデーションメッセージが表示される
 * - 正しい情報が入力された場合、ログイン処理が実行される
 */
class Case002LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required(): void
    {
        // ログインページを開く
        $response = $this->get(route('login'));

        // ログインページが正常に表示されることを確認する
        $response->assertStatus(200);

        // メールアドレスを空にしてログインを実行する
        $response = $this->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), [
                'email' => '',
                'password' => 'password123',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSee('メールアドレスを入力してください');

        // ログインしていないことを確認する
        $this->assertGuest();
    }

    public function test_password_is_required(): void
    {
        // ログインページを開く
        $response = $this->get(route('login'));

        // ログインページが正常に表示されることを確認する
        $response->assertStatus(200);

        // パスワードを空にしてログインを実行する
        $response = $this->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), [
                'email' => 'test@example.com',
                'password' => '',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSee('パスワードを入力してください');

        // ログインしていないことを確認する
        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        // ログイン用ユーザーを作成する
        $user = $this->createLoginUser();

        // ログインページを開く
        $response = $this->get(route('login'));

        // ログインページが正常に表示されることを確認する
        $response->assertStatus(200);

        // 登録されていないログイン情報でログインを実行する
        $response = $this->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        // 認証失敗メッセージが画面に表示されることを確認する
        $response->assertSee('ログイン情報が登録されていません');

        // ログインしていないことを確認する
        $this->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        // ログイン用ユーザーを作成する
        $user = $this->createLoginUser();

        // ログインページを開く
        $response = $this->get(route('login'));

        // ログインページが正常に表示されることを確認する
        $response->assertStatus(200);

        // 正しいログイン情報でログインを実行する
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // ログイン状態になっていることを確認する
        $this->assertAuthenticatedAs($user);

        // ログイン後にリダイレクトされることを確認する
        $response->assertRedirect();
    }

    /**
     * ログイン機能テスト用ユーザーを作成する
     *
     * @return \App\Models\User
     */
    private function createLoginUser(): User
    {
        return User::factory()->create([
            'name' => 'ログイン太郎',
            'email' => 'login-test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}
