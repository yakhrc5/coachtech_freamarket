<?php

namespace Tests\Feature\Items;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Condition;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\ConditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Case007 商品詳細情報取得
 *
 * 対応要件:
 * - 商品詳細ページに必要な情報が表示される
 *   （商品画像、商品名、ブランド名、価格、いいね数、コメント数、商品説明、
 *    商品情報（カテゴリ、商品の状態）、コメントしたユーザー情報、コメント内容）
 * - 複数選択されたカテゴリが表示される
 */
class Case007ItemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_detail_page_displays_required_information(): void
    {
        $data = $this->prepareItemDetailData();

        // 商品詳細ページを開く
        $response = $this->get(route('items.show', $data['item']->id));

        // すべての情報が商品詳細ページに表示されている（= 取得できている）
        $response->assertOk();

        // まずは「コントローラが必要な情報をロードして渡しているか」を担保
        $response->assertViewHas('item', function ($viewItem) use ($data): bool {
            /** @var \App\Models\Item $viewItem */
            $categoryNames = $viewItem->categories->pluck('name')->sort()->values()->all();
            $expectedCategoryNames = $data['categories']->pluck('name')->sort()->values()->all();

            return $viewItem->id === $data['item']->id
                && $viewItem->name === $data['item']->name
                && $viewItem->brand === $data['item']->brand
                && (int) $viewItem->price === (int) $data['item']->price
                && $viewItem->description === $data['item']->description
                && $viewItem->image_path === $data['item']->image_path

                // 商品情報（カテゴリ、商品の状態）
                && $viewItem->relationLoaded('categories')
                && $categoryNames === $expectedCategoryNames
                && $viewItem->relationLoaded('condition')
                && $viewItem->condition->id === $data['condition']->id

                // いいね数、コメント数（withCount の想定）
                && (int) $viewItem->likes_count === $data['likeUsers']->count()
                && (int) $viewItem->comments_count === $data['comments']->count()

                // コメント（ユーザー情報 + 内容）
                && $viewItem->relationLoaded('comments')
                && $viewItem->comments->count() === $data['comments']->count()
                && $viewItem->comments->every(fn($comment) => $comment->relationLoaded('user'));
        });

        // 次に「画面上の表示（最低限）」もチェック
        $response->assertSeeText($data['item']->name);
        $response->assertSeeText($data['item']->brand);
        $response->assertSeeText($data['item']->description);
        $response->assertSee('/storage/' . $data['item']->image_path);

        $response->assertSeeText(number_format($data['item']->price));
        $response->assertSeeText($data['condition']->name);
        $response->assertSeeText((string) $data['likeUsers']->count());
        $response->assertSeeText((string) $data['comments']->count());

        // 複数カテゴリ名が表示されている
        foreach ($data['categories'] as $category) {
            $response->assertSeeText($category->name);
        }

        // コメントしたユーザー情報 + コメント内容が表示されている
        foreach ($data['comments'] as $comment) {
            $response->assertSeeText($comment->user->name);
            $response->assertSeeText($comment->body);
        }
    }

    public function test_multiple_selected_categories_are_displayed_on_item_detail_page(): void
    {
        $data = $this->prepareItemDetailData();

        // 商品詳細ページを開く
        $response = $this->get(route('items.show', $data['item']->id));

        // 複数選択されたカテゴリが商品詳細ページに表示されている
        $response->assertOk();

        // 表示（HTML）としてカテゴリ名が全部見えること
        foreach ($data['categories'] as $category) {
            $response->assertSee($category->name);
        }

        // ビューに渡る item に categories が正しく載っていること（保険）
        $response->assertViewHas('item', function ($viewItem) use ($data): bool {
            /** @var \App\Models\Item $viewItem */
            return $viewItem->relationLoaded('categories')
                && $viewItem->categories->count() === $data['categories']->count();
        });
    }

    /**
     * 商品詳細テスト用データを準備する
     *
     * @return array{
     *   item: \App\Models\Item,
     *   condition: \App\Models\Condition,
     *   categories: \Illuminate\Support\Collection<int, \App\Models\Category>,
     *   likeUsers: \Illuminate\Support\Collection<int, \App\Models\User>,
     *   comments: \Illuminate\Support\Collection<int, \App\Models\Comment>
     * }
     */
    private function prepareItemDetailData(): array
    {
        // マスタをseedしてから、商品を作成して必要な情報を紐付ける
        $this->seed([
            ConditionsSeeder::class,
            CategoriesSeeder::class,
        ]);
        // 状態は1件あれば十分なので最初の1件を取得
        $condition = Condition::query()->firstOrFail();

        // 複数カテゴリ（最低2つ）
        $categories = Category::query()->take(2)->get();
        if ($categories->count() < 2) {
            // Seederの内容次第で不足するケース保険
            $categories = new Collection([
                Category::query()->create(['name' => 'カテゴリA']),
                Category::query()->create(['name' => 'カテゴリB']),
            ]);
        }

        // 出品者＆商品
        $seller = User::factory()->create();

        $item = Item::query()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price' => 12345,
            'image_path' => 'items/test-item.jpg',
        ]);

        // カテゴリを複数紐付け
        $item->categories()->attach($categories->pluck('id')->all());

        // いいねを押すユーザ作成（2件）
        $likeUsers = User::factory()->count(2)->create();

        $now = now();

        foreach ($likeUsers as $likeUser) {
            DB::table('likes')->insert([
                'user_id' => $likeUser->id,
                'item_id' => $item->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // コメント（3件：ユーザー情報＋内容）
        $commentUsers = User::factory()->count(3)->create();
        $bodies = ['コメント本文1', 'コメント本文2', 'コメント本文3'];

        foreach ($commentUsers as $i => $commentUser) {
            DB::table('comments')->insert([
                'user_id' => $commentUser->id,
                'item_id' => $item->id,
                'body' => $bodies[$i],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // コメントはモデルで取り直して、user も eager load しておく（assertSee用）
        $comments = Comment::query()
            ->where('item_id', $item->id)
            ->with('user')
            ->get();

        return [
            'item' => $item,
            'condition' => $condition,
            'categories' => $categories,
            'likeUsers' => $likeUsers,
            'comments' => $comments,
        ];
    }
}
