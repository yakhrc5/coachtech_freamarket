<?php

namespace Tests\Feature\Items;

use App\Models\Category;
use App\Models\Condition;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Case009 コメント送信機能
 *
 * 対応要件:
 * - ログイン済みのユーザーはコメントを送信できる
 * - ログイン前のユーザーはコメントを送信できない
 * - コメントが入力されていない場合、バリデーションメッセージが表示される
 * - コメントが255字以上の場合、バリデーションメッセージが表示される
 */
class Case009ItemCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_post_comment(): void
    {
        // コメント機能テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // コメント前の商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertOk();

        // コメント前はコメント数が0件で表示されることを確認する
        $response->assertSeeText('コメント(0)');

        // コメントを送信する
        $response = $this->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => 'テストコメント',
            ]
        );

        // コメント送信後にリダイレクトされることを確認する
        $response->assertRedirect();

        // commentsテーブルにコメントが登録されていることを確認する
        $this->assertDatabaseHas('comments', [
            'user_id' => $data['loginUser']->id,
            'item_id' => $data['item']->id,
            'body' => 'テストコメント',
        ]);

        // コメント後の商品詳細画面を再度開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertOk();

        // コメント数が1件で表示されることを確認する
        $response->assertSeeText('コメント(1)');

        // コメント内容が表示されることを確認する
        $response->assertSeeText('テストコメント');

        // コメントしたユーザー名が表示されることを確認する
        $response->assertSeeText($data['loginUser']->name);
    }

    public function test_guest_user_cannot_post_comment(): void
    {
        // コメント機能テスト用データを準備する
        $data = $this->prepareCommentData();

        // ゲスト状態で商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertOk();

        // コメント前はコメント数が0件で表示されることを確認する
        $response->assertSeeText('コメント(0)');

        // ゲスト状態でコメント送信を試みる
        $response = $this->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => 'テストコメント',
            ]
        );

        // 未ログインユーザーはログイン画面へリダイレクトされることを確認する
        $response->assertRedirect(route('login'));

        // commentsテーブルにコメントが登録されていないことを確認する
        $this->assertDatabaseMissing('comments', [
            'item_id' => $data['item']->id,
            'body' => 'テストコメント',
        ]);
    }

    public function test_comment_validation_required(): void
    {
        // コメント機能テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // 商品詳細画面からコメント送信した想定で、未入力のまま送信する
        $response = $this->from(
            route('items.show', ['item_id' => $data['item']->id])
        )->followingRedirects()->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => '',
            ]
        );

        // 元の画面に戻り、バリデーションメッセージが表示されることを確認する
        $response->assertSeeText('コメントを入力してください');

        // commentsテーブルに1件も登録されていないことを確認する
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_validation_max_length(): void
    {
        // コメント機能テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // 256文字のコメントを作成する
        $longComment = str_repeat('あ', 256);

        // 商品詳細画面からコメント送信した想定で、256文字のコメントを送信する
        $response = $this->from(
            route('items.show', ['item_id' => $data['item']->id])
        )->followingRedirects()->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => $longComment,
            ]
        );

        // 元の画面に戻り、バリデーションメッセージが表示されることを確認する
        $response->assertSeeText('コメントは255文字以内で入力してください');

        // commentsテーブルに1件も登録されていないことを確認する
        $this->assertDatabaseCount('comments', 0);
    }

    /**
     * コメント機能テスト用データを準備する
     *
     * @return array{
     *   loginUser: \App\Models\User,
     *   item: \App\Models\Item
     * }
     */
    private function prepareCommentData(): array
    {
        // マスタをseedしてから、商品を作成して必要な情報を紐付ける
        $this->seed([
            ConditionsSeeder::class,
            CategoriesSeeder::class,
        ]);

        // コメントするログインユーザーを作成する
        /** @var \App\Models\User $loginUser */
        $loginUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 出品者ユーザーを作成する
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create();

        // 状態とカテゴリは最初の1件を取得する
        /** @var \App\Models\Condition $condition */
        $condition = Condition::query()->firstOrFail();

        /** @var \App\Models\Category $category */
        $category = Category::query()->firstOrFail();

        // コメント対象の商品を作成する
        /** @var \App\Models\Item $item */
        $item = Item::query()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price' => 12345,
            'image_path' => 'items/test-item.jpg',
        ]);

        // カテゴリを紐付ける
        $item->categories()->attach($category->id);

        return [
            'item' => $item,
            'loginUser' => $loginUser,
        ];
    }
}
