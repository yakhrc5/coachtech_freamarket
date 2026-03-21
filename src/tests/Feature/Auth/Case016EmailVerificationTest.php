<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Case016 メール認証機能
 *
 * 対応要件:
 * - 会員登録後、認証メールが送信される
 * - メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
 * - メール認証サイトのメール認証を完了すると、プロフィール設定画面に遷移する
 */
class Case016EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_after_register(): void
    {
        // 認証メール通知を fake にする
        Notification::fake();

        // 会員登録を実行する
        $response = $this->post(route('register'), [
            'name' => '認証太郎',
            'email' => 'verify-test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // usersテーブルに会員情報が登録されていることを確認する
        $this->assertDatabaseHas('users', [
            'name' => '認証太郎',
            'email' => 'verify-test@example.com',
        ]);

        // 登録されたユーザーを取得する
        $user = User::query()->where('email', 'verify-test@example.com')->firstOrFail();

        // 認証メール通知が送信されていることを確認する
        Notification::assertSentTo($user, VerifyEmail::class);

        // 登録後にメール認証誘導画面へリダイレクトされることを確認する
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verify_notice_page_has_link_to_mailhog(): void
    {
        // 未認証ユーザーを作成する
        $user = $this->createUnverifiedUser();

        // 未認証ユーザーでログインする
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        // メール認証誘導画面を開く
        $response = $this->get(route('verification.notice'));

        // メール認証誘導画面が正常に表示されることを確認する
        $response->assertOk();

        // 「認証はこちらから」リンクが表示されていることを確認する
        $response->assertSeeText('認証はこちらから');

        // Mailhog のURLがリンク先として設定されていることを確認する
        $response->assertSee('href="http://localhost:8025"', false);
    }

    public function test_verified_user_is_redirected_to_profile_edit(): void
    {
        // 未認証ユーザーを作成する
        $user = $this->createUnverifiedUser();

        // 未認証ユーザーでログインする
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        // メール認証誘導画面を一度開いて、認証後の遷移先セッションを設定する
        $response = $this->get(route('verification.notice'));

        // メール認証誘導画面が正常に表示されることを確認する
        $response->assertOk();

        // 認証用の署名付きURLを生成する
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // 認証URLにアクセスしてメール認証を完了する
        $response = $this->get($verificationUrl);

        // ユーザーが認証済みになっていることを確認する
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // 認証完了後にプロフィール設定画面へリダイレクトされることを確認する
        $response->assertRedirect(route('profile.edit'));
    }

    /**
     * 未認証ユーザーを作成する
     *
     * @return \App\Models\User
     */
    private function createUnverifiedUser(): User
    {
        return User::factory()->create([
            'name' => '未認証太郎',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);
    }
}
