# Attendance_Management（勤怠管理アプリ）

Laravel + Laravel Sail（Docker）で動作する勤怠管理アプリです。  
ローカル環境では MySQL / Redis / Mailpit を起動し、認証は Laravel Fortify（メール認証あり）を使用します。

-   Repository: https://github.com/takuya0521/Attendance_Management

---

## 環境構築

-   Docker（Sail）でコンテナ起動 → アプリキー生成 → マイグレーションまで実行します
-   MySQL のホスト側ポートは 3306 競合回避のため **3307** を使用します（必要に応じて変更可能）

---

## 使用技術（実行環境）

-   Laravel 12.x
-   PHP 8.5（Sail）
-   MySQL 8.4（Sail）
-   Redis（Sail）
-   Mailpit（Sail）
-   Laravel Fortify（会員登録 / ログイン / メール認証）

---

## セットアップ手順（コピペ用）

> この手順は WSL / macOS / Linux のターミナルで実行する想定です。

### 1) リポジトリ取得

    git clone https://github.com/takuya0521/Attendance_Management.git
    cd Attendance_Management

### 2) .env 作成

    cp .env.example .env

### 3) MySQL ポート競合対策（3306 使用中の場合）

> PC 側で 3306 を使っている場合、ホスト公開ポートを 3307 に逃がします  
> ※コンテナ内（Laravel → MySQL）は mysql:3306 のまま

    grep -q '^FORWARD_DB_PORT=' .env && \
      sed -i 's/^FORWARD_DB_PORT=.*/FORWARD_DB_PORT=3307/' .env || \
      echo 'FORWARD_DB_PORT=3307' >> .env

### 4) コンテナ起動（Docker/Sail）

    chmod +x vendor/bin/sail
    ./vendor/bin/sail up -d

### 5) アプリキー作成

    ./vendor/bin/sail artisan key:generate

### 6) マイグレーション

    ./vendor/bin/sail artisan migrate

### 7) 動作確認

    ./vendor/bin/sail ps
    ./vendor/bin/sail artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
    curl -I http://localhost | head

---

## Fortify（認証）

-   会員登録 / ログイン / メール認証（Email Verification）を提供します
-   メールは Mailpit で確認します

### 動作確認手順

1. http://localhost/register から会員登録
2. Mailpit（http://localhost:8025）で認証メールを確認
3. 認証リンクをクリック
4. http://localhost/login からログイン

---

## ER 図

-   作成した ER 図の画像を配置して貼り付けます（例：docs/er.png）

![ER図](docs/er.png)

---

## URL

-   開発環境（アプリ）：http://localhost
-   Mailpit：http://localhost:8025

---

## よく使うコマンド

### 起動

    ./vendor/bin/sail up -d

### 停止

    ./vendor/bin/sail down

### DB 初期化（ボリューム削除して作り直し）

    ./vendor/bin/sail down -v --remove-orphans
    ./vendor/bin/sail up -d
    ./vendor/bin/sail artisan migrate

### キャッシュ削除（設定反映がうまくいかないとき）

    ./vendor/bin/sail artisan optimize:clear

---

## トラブルシューティング

### MySQL が 3306 で起動できない

-   エラー例：ports are not available ... 0.0.0.0:3306
-   対処：.env に FORWARD_DB_PORT=3307 を設定し、コンテナを作り直す

    echo 'FORWARD_DB_PORT=3307' >> .env
    ./vendor/bin/sail down -v --remove-orphans
    ./vendor/bin/sail up -d

### localhost が開けない（80 番が競合する）

-   対処：.env に APP_PORT=8080 を設定して再起動

    grep -q '^APP_PORT=' .env && \
     sed -i 's/^APP_PORT=.\*/APP_PORT=8080/' .env || \
     echo 'APP_PORT=8080' >> .env

    ./vendor/bin/sail down
    ./vendor/bin/sail up -d

-   アクセス：http://localhost
