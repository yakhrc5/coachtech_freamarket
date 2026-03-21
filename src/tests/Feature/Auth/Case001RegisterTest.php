<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Case001 会員登録機能
 *
 * 対応要件:
 * - 名前が入力されていない場合、バリデーションメッセージが表示される
 * - メールアドレスが入力されていない場合、バリデーションメッセージが表示される
 * - パスワードが入力されていない場合、バリデーションメッセージが表示される
 * - パスワードが7文字以下の場合、バリデーションメッセージが表示される
 * - パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
 * - 全ての項目が正しい場合、会員情報が登録される
 */
class Case001RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_required(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // 名前を空にして会員登録を実行する
        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), [
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSeeText('お名前を入力してください');

        // usersテーブルに会員情報が登録されていないことを確認する
        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_is_required(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // メールアドレスを空にして会員登録を実行する
        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), [
                'name' => 'テスト太郎',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSeeText('メールアドレスを入力してください');

        // usersテーブルに会員情報が登録されていないことを確認する
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_is_required(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // パスワードを空にして会員登録を実行する
        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), [
                'name' => 'テスト太郎',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => 'password123',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSeeText('パスワードを入力してください');

        // usersテーブルに会員情報が登録されていないことを確認する
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // 7文字のパスワードで会員登録を実行する
        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), [
                'name' => 'テスト太郎',
                'email' => 'test@example.com',
                'password' => 'pass123',
                'password_confirmation' => 'pass123',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSeeText('パスワードは8文字以上で入力してください');

        // usersテーブルに会員情報が登録されていないことを確認する
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_confirmation_must_match(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // パスワードと確認用パスワードを不一致にして会員登録を実行する
        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('register'), [
                'name' => 'テスト太郎',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password999',
            ]);

        // バリデーションメッセージが画面に表示されることを確認する
        $response->assertSeeText('パスワードと一致しません');

        // usersテーブルに会員情報が登録されていないことを確認する
        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_register(): void
    {
        // 会員登録ページを開く
        $response = $this->get(route('register'));

        // 会員登録ページが正常に表示されることを確認する
        $response->assertOk();

        // 正しい入力値で会員登録を実行する
        $response = $this->post(route('register'), [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // usersテーブルに会員情報が登録されていることを確認する
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);

        // 登録後にログイン状態になっていることを確認する
        $this->assertAuthenticated();

        // メール認証誘導画面へ遷移する構成の場合
        $response->assertRedirect(route('verification.notice'));
    }
}
