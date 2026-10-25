# Cytech EC — 開発環境・DB基盤

実装先：`/home/keisuke/CyTech_STEP9/cytech_STEP9_app`

## 構成

- PHP 8.5.12 / Laravel 13 / Laravel Sail
- Laravel Breeze 2.4.2（Blade・PHPUnit）
- Vite 8 / BEM形式の外部CSS / 外部JavaScript（メニュー・モーダル等）
- MySQL 8.4 / phpMyAdmin / Mailpit
- Laravel Boost（AGENTS.mdの指定に従った開発用パッケージ）

現在は開発環境とECアプリのDB・Modelの基盤までを実装した段階です。登録画面の漢字名・カナ名の入力と検証は、必須カラム追加後も登録できるようSTEP 02に前倒ししました。登録後のログイン画面への遷移、商品画面などは後続STEPで実装します。Breeze標準のダッシュボード・プロフィール等も、後続STEPで仕様に合わせて整理します。

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

phpMyAdminはログイン画面を使用し、DBパスワードをComposeファイルへ固定しません。会社登録・所属会社の設定方法は下記のSTEP 02の手順を参照してください。問い合わせフォームはSTEP 13で実装します。

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

## STEP 02：DB・Modelの基盤（2026-10-25）

### 追加内容

- `companies`・`products`・`sales`・`likes`を追加し、既存の`users`に`name_kanji`・`name_kana`・`company_id`を追加。
- `name_kanji`はDBでNULL不可、`name_kana`・`company_id`はNULL可。登録フォームでは漢字名・カナ名とも必須。
- 商品は`deleted_at`による論理削除。通常の関連取得では非表示とし、`Sale::product()`は削除済み商品も参照。
- 購入履歴の商品名・説明・価格は現在の商品データを参照。購入時点の情報保存用カラムは追加していない。
- お気に入りはユーザーと商品の組み合わせを一意とする。削除済み商品のお気に入り行は保持するが、通常の商品関連は取得できない。
- 外部キーは参照先の物理削除を制限し、商品削除で購入履歴を連鎖削除しない。関連データがあるアカウントの物理削除もDBで制限される。
- メールの一意制約は既存定義を維持。外部キー索引と購入履歴用の`user_id, created_at, id`索引を設定。
- `sessions`・`password_reset_tokens`・キャッシュ・キュー関連はLaravelの補助テーブルとして維持。Sanctumはまだ追加していない。

既存のユーザー作成マイグレーションを編集せず、追加マイグレーションで拡張しています。今回の開発DBには既存ユーザーがいないことを確認して適用しました。ユーザーが存在する別環境では、漢字名を勝手に補完しないよう追加処理を停止します。その場合は既存ユーザーの移行方針を決めてから対応してください。

### 会社登録・所属会社の設定（phpMyAdmin）

1. http://localhost:8080 にログインし、`.env`の`DB_DATABASE`に対応する開発用DBを選択する。
2. SQLタブで会社を登録する。会社名は実際の値に置き換える。

```sql
INSERT INTO companies (company_name, created_at, updated_at)
VALUES ('株式会社サンプル', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

SELECT id, company_name FROM companies ORDER BY id ASC;
```

3. アプリの登録画面でユーザーを登録する。会社選択欄は設けておらず、登録時の`company_id`はNULLとなる。
4. phpMyAdminで会社ID・対象ユーザーIDを確認する。

```sql
SELECT id, name, email, company_id FROM users ORDER BY id ASC;
```

5. 次の例の`1`（会社ID）・`2`（ユーザーID）を、確認した実際のIDへ置き換えて実行する。

```sql
UPDATE users
SET company_id = 1, updated_at = CURRENT_TIMESTAMP
WHERE id = 2;

SELECT users.id, users.name, users.company_id, companies.company_name
FROM users
LEFT JOIN companies ON companies.id = users.company_id
WHERE users.id = 2;
```

会社データやサンプルユーザーは、今回の開発DBには自動登録していません。会社の登録と所属会社設定は原資料に従って手動で行います。

### 検証用データ

Factoryはテスト内で必要なときに使用します。次の例は、専用テストDBでの検証を想定したPHPコードです。

```php
$company = Company::factory()->create();
$seller = User::factory()->for($company)->create();
$buyer = User::factory()->create();
$product = Product::factory()->for($seller)->create();
$sold_out_product = Product::factory()->for($seller)->soldOut()->create();
$deleted_product = Product::factory()->for($seller)->deleted()->create();
$sale = Sale::factory()->for($buyer)->for($deleted_product)->create();
$like = Like::factory()->for($buyer)->for($product)->create();
```

各クラスは`App\Models`配下です。`User::factory()->withCompany()`で所属会社付きユーザー、`withoutKana()`でカナ名NULLの既存データ例を作成できます。`ProductFactory`は出品者の所属会社を商品に設定するため、指定する出品者には会社を関連付けてください。

Factoryの画像パスは検証用のダミー値で、画像ファイルは作成しません。また、購入記録のFactoryは購入処理そのものではなく、在庫を減算しません。在庫減算はSTEP 10で実装します。

### テストDBの保護と確認結果

テスト設定で接続先をMySQLの`testing` DBへ固定し、アプリ起動時にもその設定を確認します。別の接続先やDB URLが設定されている場合は、DBを初期化する前にテストを停止します。

- 専用MySQL `testing` DBでマイグレーション・5件分のロールバック・再適用に成功。
- Modelの関連、論理削除後の購入履歴、現在の商品情報の参照、外部キー、物理削除制限、お気に入り重複防止を確認。
- 漢字名・カナ名の保存・不正入力の拒否・会社IDの不正な送信の除外を確認。
- 全51テスト（186 assertions）・Pint・フロントエンドビルドに成功。
- 開発DBには通常の`migrate`で5件の追加マイグレーションを適用済み。開発DBの再作成やデータ投入は行っていない。
- 登録後の自動ログイン・ダッシュボード遷移は現状を維持。仕様に合わせた遷移変更はSTEP 03で実装する。
