<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\Condition;
use App\Models\Category;
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
        // テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // コメント前の商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // コメント前はコメント数が0件で表示されることを確認する
        $response->assertSee('コメント(0)');

        // コメント送信する
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
        // コメント後の商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);
        // コメント数が1件で表示されることを確認する
        $response->assertSee('コメント(1)');
        // コメント内容が表示されることを確認する
        $response->assertSee('テストコメント');
        // コメントしたユーザー名が表示されることを確認する
        $response->assertSee($data['loginUser']->name);
    }

    public function test_guest_user_cannot_post_comment(): void
    {
        // テスト用データを準備する
        $data = $this->prepareCommentData();

        // ゲスト状態で商品詳細画面を開く
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        // 商品詳細画面が正常に表示されることを確認する
        $response->assertStatus(200);

        // コメント前はコメント数が0件で表示されることを確認する
        $response->assertSee('コメント(0)');

        // コメント送信を試みる（ゲストはコメントできない想定）
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

    public function test_comment_validation(): void
    {
        // テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // 商品詳細画面からコメント送信した想定でPOSTする
        $response = $this->from(
            route('items.show', ['item_id' => $data['item']->id])
        )->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => '',
            ]
        );

        // バリデーションエラーになり、元の画面へ戻ることを確認する
        $response->assertRedirect(route('items.show', ['item_id' => $data['item']->id]));
        $response->assertSessionHasErrors([
            'body' => 'コメントを入力してください',
        ]);

        // 次のリクエストでエラーメッセージが画面に表示されることを確認する
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );
        // 画面にバリデーションメッセージが表示されることを確認する
        $response->assertStatus(200);
        $response->assertSee('コメントを入力してください');

        // commentsテーブルに1件も登録されていないことを確認する
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_validation_max_length(): void
    {
        // テスト用データを準備する
        $data = $this->prepareCommentData();

        // コメントするユーザーでログインする
        $this->actingAs($data['loginUser']);
        $this->assertAuthenticatedAs($data['loginUser']);

        // 256文字のコメントを作成する
        $longComment = str_repeat('あ', 256); // 256文字のコメント

        // 商品詳細画面からコメント送信した想定でPOSTする
        $response = $this->from(
            route('items.show', ['item_id' => $data['item']->id])
        )->post(
            route('items.comments.store', ['item_id' => $data['item']->id]),
            [
                'body' => $longComment,
            ]
        );

        // バリデーションエラーになり、元の画面へ戻ることを確認する
        $response->assertRedirect(route('items.show', ['item_id' => $data['item']->id]));
        $response->assertSessionHasErrors([
            'body' => 'コメントは255文字以内で入力してください',
        ]);

        // 次のリクエストでエラーメッセージが画面に表示されることを確認する
        $response = $this->get(
            route('items.show', ['item_id' => $data['item']->id])
        );

        $response->assertStatus(200);
        $response->assertSee('コメントは255文字以内で入力してください');

        // commentsテーブルに1件も登録されていないことを確認する
        $this->assertDatabaseCount('comments', 0);
    }

    /**
     * 商品詳細テスト用データを準備する
     *
     * @return array{
     *  loginUser: \App\Models\User,
     *  seller: \App\Models\User,
     *  item: \App\Models\Item
     * }
     */
    private function prepareCommentData(): array
    {

        // マスタをseedしてから、商品を作成して必要な情報を紐付ける
        $this->seed([
            ConditionsSeeder::class,
            CategoriesSeeder::class,
        ]);
        // loginUserを作成
        $loginUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        // 出品者を作成
        $seller = User::factory()->create();

        // 状態、カテゴリは最初の1件を取得
        $condition = Condition::query()->firstOrFail();
        $category = Category::query()->firstOrFail();

        $item = Item::query()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price' => 12345,
            'image_path' => 'items/test-item.jpg',
        ]);

        // カテゴリを紐付け
        $item->categories()->attach($category->id);

        return [
            'item' => $item,
            'seller' => $seller,
            'loginUser' => $loginUser,
        ];
    }

}
