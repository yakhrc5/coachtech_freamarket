# テーブル定義一覧（フリマアプリ）

> 命名方針
> - テーブル名: 小文字 + スネークケース
> - 通常テーブル: 複数形
> - 中間テーブル: `category_item`（Laravel慣習の例外）
> - 外部キー: `xxx_id`

---

## users

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| name | varchar(255) | No |  |  |  |  |
| email | varchar(255) | No |  | Yes |  |  |
| email_verified_at | timestamp | Yes |  |  |  |  |
| password | varchar(255) | No |  |  |  |  |
| profile_image_path | varchar(255) | Yes |  |  |  |  |
| postal_code | varchar(8) | Yes |  |  |  |  |
| address | varchar(255) | Yes |  |  |  |  |
| building | varchar(255) | Yes |  |  |  |  |
| remember_token | varchar(100) | Yes |  |  |  |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## conditions

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| name | varchar(255) | No |  | Yes |  |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## categories

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| name | varchar(255) | No |  | Yes |  |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## items

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| user_id | unsigned bigint | No |  |  | users(id) | 出品者 |
| condition_id | unsigned bigint | No |  |  | conditions(id) | 商品状態 |
| name | varchar(255) | No |  |  |  |  |
| brand | varchar(255) | Yes |  |  |  |  |
| description | text | No |  |  |  |  |
| price | unsigned integer | No |  |  |  |  |
| image_path | varchar(255) | No |  |  |  | `storage` 保存パス |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## category_item（中間テーブル）

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| item_id | unsigned bigint | No | Yes（複合） |  | items(id) |  |
| category_id | unsigned bigint | No | Yes（複合） |  | categories(id) |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

### 補足
- 複合主キー: `(item_id, category_id)`

---

## likes

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| user_id | unsigned bigint | No |  |  | users(id) |  |
| item_id | unsigned bigint | No |  |  | items(id) |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

### 補足
- 複合ユニーク制約推奨: `(user_id, item_id)`（同一ユーザーの重複いいね防止）

---

## comments

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| user_id | unsigned bigint | No |  |  | users(id) | コメント投稿者 |
| item_id | unsigned bigint | No |  |  | items(id) | 対象商品 |
| body | varchar(255) | No |  |  |  |  |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## purchases

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| user_id | unsigned bigint | No |  |  | users(id) | 購入者 |
| item_id | unsigned bigint | No |  | Yes（推奨） | items(id) | フリマ想定: 1商品1購入 |
| payment_method_id | unsigned bigint | No |  |  | payment_methods(id) |  |
| postal_code | varchar(8) | No |  |  |  | 配送先 |
| address | varchar(255) | No |  |  |  | 配送先 |
| building | varchar(255) | Yes |  |  |  | 配送先（任意） |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

### 補足
- `item_id` はフリマ仕様上、重複しない想定のためユニーク制約を付ける設計がわかりやすい

---

## payment_methods

| Column | Type | Null | PK | UK | FK | Notes |
|---|---|---:|---:|---:|---|---|
| id | unsigned bigint | No | Yes |  |  |  |
| name | varchar(50) | No |  | Yes |  | 例: コンビニ支払い / カード支払い |
| created_at | timestamp | Yes |  |  |  |  |
| updated_at | timestamp | Yes |  |  |  |  |

---

## リレーション要約（実装確認用）

- `users` 1 : N `items`
- `conditions` 1 : N `items`
- `items` N : N `categories`（`category_item`）
- `users` 1 : N `likes`
- `items` 1 : N `likes`
- `users` 1 : N `comments`
- `items` 1 : N `comments`
- `users` 1 : N `purchases`
- `items` 1 : 0..1 `purchases`
- `payment_methods` 1 : N `purchases`