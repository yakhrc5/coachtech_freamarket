# coachtech_freamarket

## 環境構築
**Dockerビルド**
1. `git clone git@github.com:〇〇〇`
2. `cd 〇〇〇`
3. DockerDesktopアプリを立ち上げる
4. `docker compose up -d --build`

**Laravel環境構築**
1. `docker-compose exec php bash`
2. `composer install`
3. `cp .env.example .env`「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. アプリケーションキーの作成
``` bash
php artisan key:generate
```
6. マイグレーションの実行
``` bash
php artisan migrate
```
7. シーディングの実行
``` bash
php artisan db:seed
```

## URL
- 開発環境: http://localhost/
- phpMyAdmin: http://localhost:8080/

## 使用技術(実行環境)
- PHP 8.1.34
- Laravel 8.83.29
- MySQL 8.0.26
- nginx 1.21.1
- JavaScript（Vanilla JS）
- Docker / Docker Compose
- HTML / CSS（Bladeテンプレート）

## ER図

![alt text](er_diagram.png)