# Cytech EC — STEP 01 開発環境

実装先：`/home/keisuke/CyTech_STEP9/cytech_STEP9_app`

## 構成

- PHP 8.5.12 / Laravel 13 / Laravel Sail
- Laravel Breeze 2.4.2（Blade・PHPUnit）
- Vite 8 / BEM形式の外部CSS / 外部JavaScript（メニュー・モーダル等）
- MySQL 8.4 / phpMyAdmin / Mailpit
- Laravel Boost（AGENTS.mdの指定に従った開発用パッケージ）

現在はBreeze標準の認証画面までを導入した段階です。漢字名・カナ名、登録後のログイン画面への遷移、商品画面などは後続STEPで実装します。Breeze標準のダッシュボード・プロフィール等も、後続STEPで仕様に合わせて整理します。

コーディング規約への対応として、生成画面のTailwindクラスを通常CSSへ置き換え、Viewファイル名をスネークケース、HTMLのclassをBEM、IDをローワーキャメルへ統一しています。画面操作は外部JavaScript、入力検証はForm Request、DB保存はUserモデルへ配置します。

## 初回セットアップ

Docker EngineとDocker Composeを起動しておきます。以下はプロジェクトルートで実行します。既存の`.env`は上書きせず、初回のみコピーしてください。

```bash
cd /home/keisuke/CyTech_STEP9/cytech_STEP9_app
cp .env.example .env
```

`.env`の`WWWUSER`・`WWWGROUP`はホスト側の`id -u`・`id -g`に合わせます。DBの初期パスワードは開発用です。変更する場合はDBの初回起動前に設定してください。既存DBボリュームがある場合、環境変数を変えるだけではDB内の認証情報は変わりません。

ホストにPHP・Composerがない場合、依存パッケージを初回だけコンテナから導入できます。

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install --no-interaction
```

その後、Sailで起動します。

```bash
./vendor/bin/sail build
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

`migrate`は既存データを削除しない通常の適用です。`migrate:fresh`や`down -v`は通常の起動には使いません。

## 日常の起動・停止

```bash
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
```

Viteは起動したターミナルを使用します。停止はCtrl+C。Viteを使わず確認する場合は`./vendor/bin/sail npm run build`を実行します。

```bash
./vendor/bin/sail down
```

## 接続先

`.env.example`のポート設定での接続先です。既存`.env`で変更している場合はそちらを優先します。

| 用途 | URL・設定 |
|---|---|
| アプリ | http://localhost:8000 |
| ログイン（Breeze標準） | http://localhost:8000/login |
| ユーザー登録（Breeze標準） | http://localhost:8000/register |
| Vite | http://localhost:5173 |
| phpMyAdmin | http://localhost:8080（ホストはMySQLサービス。`.env`のDBユーザー・パスワードでログイン） |
| Mailpit受信画面 | http://localhost:8025 |
| アプリからのSMTP | `mailpit:1025` |
| アプリからのDB | `mysql:3306` |

ポート変更には`APP_PORT`・`VITE_PORT`・`FORWARD_PHPMYADMIN_PORT`・`FORWARD_MAILPIT_DASHBOARD_PORT`等を使用します。アプリのポートを変更したら`APP_URL`も合わせ、Viteのポートを変更したらViteの起動引数も合わせます。

phpMyAdminはログイン画面を使用し、DBパスワードをComposeファイルへ固定しません。会社登録・所属会社の設定は、STEP 02でテーブルを作成してから行います。問い合わせフォームはSTEP 13で実装します。

## 確認コマンド

```bash
./vendor/bin/sail php -v
./vendor/bin/sail artisan --version
./vendor/bin/sail artisan route:list
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test --compact
```

自動テストは`phpunit.xml`で専用の`testing`データベースを指定しています。SailのMySQL初回作成時にテスト用DBが作成されます。既存ボリュームにない場合は、テストDBだけを別途用意してください。開発DBに対してテストのDB初期化を実行しないでください。

## PHPバージョンの固定

`docker/8.5/`はSail 1.69.0のランタイムを基に配置しています。DockerfileでPHP本体に対応するAPTパッケージ版を指定し、ビルド時に`PHP_VERSION === "8.5.12"`を確認します。Sailの更新だけでこのDockerfileは置き換わりません。

配布元から指定パッケージ版が取得できなくなった場合、ビルドは失敗します。別バージョンへ自動的に切り替えず、指定条件を確認してください。ビルド済みイメージの保存も長期再現性のための選択肢です。

## Git

Laravelセットアップ後にGit設定済みです。`.env`・`vendor/`・`node_modules/`・ビルド成果物は管理対象外、`composer.lock`・`package-lock.json`は管理対象です。コミットはユーザーがSTEPごとに行います。

仕様書・計画書は親フォルダーにあります。

- `../CyTech_STEP9_アプリ仕様書.md`
- `../CyTech_STEP9_アプリ作成計画書.md`

## STEP 01の確認状況（2026-10-24）

- 再構築前：ビルド成功、Breeze生成テスト25件成功（61 assertions）、Pint・Composer設定検証成功。
- 再構築前：ログイン・登録・phpMyAdmin・MailpitがHTTP 200で応答。
- PHPパッケージ版を指定したイメージのビルド成功。ビルド中にPHP 8.5.12を検証し、新コンテナの起動まで完了。
- 再構築後のバージョン・ビルド・テスト・サービス状態、およびブラウザでのログイン・登録・phpMyAdmin・Mailpitの確認は、ユーザーからすべて問題なしとの報告を受けた。STEP 01は完了。
- Gitコミットはユーザーが行う。

## 参照資料

- [Laravel Breeze](https://github.com/laravel/breeze)
- [phpMyAdmin Docker設定](https://docs.phpmyadmin.net/en/latest/setup.html#installing-using-docker)

## コーディング規約への対応確認（2026-10-25）

- Tailwind CSS・Alpine.jsの依存を削除し、外部の通常CSS・JavaScriptへ移行。
- Breeze生成Viewも含めてBEMクラス・ローワーキャメルのID・スネークケースのViewファイル名へ統一。
- 既存Controllerの入力検証をForm Requestへ移し、ユーザーの登録・更新・削除・パスワード保存をUserモデルへ集約。
- ビルド・Pint・既存の25テスト（61 assertions）に成功。
- Chromiumで認証画面、登録・ログイン・ログアウト、プロフィール更新、デスクトップのドロップダウン、モバイルメニュー、モーダルのフォーカス・キャンセル・Esc・入力エラー後の再表示・確定処理を確認。アプリ側のクラス名・ID・ラベルの対応も確認。
- ブラウザ確認は専用の`testing`データベースを使用。開発用DBのユーザーデータは変更していない。
- コミットはユーザーが行う。
