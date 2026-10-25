# Cytech EC

商品を出品・検索・購入する学習用ECアプリです。11画面、非同期検索、お気に入り、画像付き商品管理、購入履歴、アカウント編集、お問い合わせ、Bearer認証の購入APIを実装しています。

コードを初めて読む方は、[初心者向け：コードを読む順番の手順書](docs/code_reading_guide.md)から進めてください。

**提出前の残項目：Postmanアプリでの実機確認。** 自動テスト・HTTP検証の成功と区別して記録しています。確認項目と結果は[全体確認記録](docs/verification.md)、過去の工程別記録は[開発履歴](docs/development_history.md)を参照してください。

## 環境

PHP 8.5.12、Laravel 13.38.0、Laravel Sail、Breeze 2.4.2（Blade）、Sanctum 4.3.3、MySQL 8.4、Vite 8、phpMyAdmin、Mailpit。実装先は`/home/keisuke/CyTech_STEP9/cytech_STEP9_app`です。依存パッケージは`composer.lock`・`package-lock.json`で管理します。

PHPのDockerランタイムは`docker/8.5/`に配置し、DockerfileでPHPパッケージ版を指定してビルド時に8.5.12を検証します。指定パッケージが配布元から取得できなくなった場合、別バージョンへ自動変更せずビルドが失敗します。

## 初回セットアップ

Docker EngineとDocker Composeを起動し、以下をプロジェクトルートで実行してください。既存の`.env`は上書きせず、初回だけコピーします。

```bash
cd /home/keisuke/CyTech_STEP9/cytech_STEP9_app
cp .env.example .env
```

`.env`の`WWWUSER`・`WWWGROUP`をホストの`id -u`・`id -g`に合わせます。ポート競合がある場合は初回起動前に変更してください。DBパスワードは開発用の初期値です。既存DBボリュームがある場合、環境変数を変えるだけではDB側の認証情報は変わりません。

ホストにPHP・Composerがない場合は、初回の依存導入にComposerコンテナを使用できます。

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install --no-interaction
```

ホストのPHP・Composerを使用する場合は`composer install --no-interaction`を実行します。その後、Sailでセットアップします。

```bash
./vendor/bin/sail build
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

`migrate`は通常の差分適用です。`migrate:fresh`や`down -v`は開発データを消すため、通常の起動には使いません。既存ユーザーに漢字名がない旧DBへプロフィール項目を追加する場合、マイグレーションは名前を勝手に補完せず停止します。既存データの移行方針を決めて対応してください。

登録済みユーザーやサンプル商品は自動投入しません。ブラウザからユーザー登録し、出品者の会社を設定してください。

## 起動・接続先

日常の起動は`./vendor/bin/sail up -d`、停止は`./vendor/bin/sail down`です。フロントエンド開発時は`./vendor/bin/sail npm run dev`を別ターミナルで起動し、Ctrl+Cで停止します。通常の確認はビルド済みアセットで行えます。

| 用途 | `.env.example`での接続先 |
|---|---|
| アプリ・ログイン | http://localhost:8000/login |
| ユーザー登録 | http://localhost:8000/register |
| 商品一覧・マイページ | `/products`・`/mypage`（ログイン必須） |
| アカウント編集・お問い合わせ | `/account/edit`・`/contact`（ログイン必須） |
| phpMyAdmin | http://localhost:8080 |
| Mailpit | http://localhost:8025 |
| Vite | http://localhost:5173 |
| コンテナからのDB・SMTP | `mysql:3306`・`mailpit:1025` |

ポートは`APP_PORT`・`VITE_PORT`・`FORWARD_DB_PORT`・`FORWARD_PHPMYADMIN_PORT`・`FORWARD_MAILPIT_PORT`・`FORWARD_MAILPIT_DASHBOARD_PORT`で変更します。アプリのポート変更時は`APP_URL`も合わせ、Viteの変更時は起動引数も合わせてください。設定変更後、キャッシュがある場合は`./vendor/bin/sail artisan config:clear`を実行します。

## 会社登録・所属会社の設定

1. アプリで出品者と購入者を登録します。登録後は自動ログインせずログイン画面へ戻ります。
2. phpMyAdminへ`.env`のDBユーザー・パスワードでログインし、`DB_DATABASE`に対応する開発DBを選びます。
3. SQLタブで会社を登録し、IDを確認します。

```sql
INSERT INTO companies (company_name, created_at, updated_at)
VALUES ('株式会社サンプル', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
SELECT id, company_name FROM companies ORDER BY id ASC;
SELECT id, name, email, company_id FROM users ORDER BY id ASC;
```

4. 実際の会社ID・出品者IDに置換して所属会社を設定します。

```sql
UPDATE users SET company_id = 1, updated_at = CURRENT_TIMESTAMP WHERE id = 2;
SELECT users.id, users.name, users.company_id, companies.company_name
FROM users LEFT JOIN companies ON companies.id = users.company_id WHERE users.id = 2;
```

会社未設定のユーザーは出品できません。購入者は会社未設定でも購入できます。会社管理画面はありません。

## 主な操作と仕様

- ログイン後は商品一覧、ログアウト後はログイン画面へ移動します。認証画面以外の9画面はログイン必須です。
- 出品者はマイページから商品登録・詳細・編集・削除を操作します。商品入力は登録・編集で共通化し、他人の編集・更新・削除を拒否します。
- 一覧は自分以外の未削除商品を商品番号昇順で表示します。商品名の部分一致・最低価格・最高価格はAND条件で検索し、価格境界を含みます。片側指定と空条件に対応し、画面全体を再読み込みせず結果を反映します。
- 商品詳細のハートは未登録が白、登録済みが赤です。状態文言も表示し、再表示しても本人の状態を保持します。
- 他人の商品を数量指定で購入し、確認モーダルで確定すると購入記録を作成して在庫を減らします。成功後は商品一覧へ戻ります。売切れ・在庫超過・自分の商品は購入できません。
- 商品削除は確認後に論理削除します。画像・購入記録は保持し、購入履歴は削除済み商品も参照します。履歴の名前・説明・価格は現在の商品情報です。購入日は昇順、同日時は購入ID昇順です。
- アカウント編集は本人のユーザー名・メール・漢字名・カナ名だけを更新し、更新後はマイページへ戻ります。本人のメール据え置きは可能です。

価格・在庫は0〜2147483647、購入数量は1〜2147483647の整数です。商品名・説明・アカウント項目は255文字以内。名前・カナ等の許可文字は各Form Requestで定義しています。パスワードは半角英数字8〜72文字・確認一致です。

画像はJPEG・PNG・WebP、最大2MiB。公開ストレージの`products/`へ生成ファイル名で保存します。編集時に画像未選択なら既存画像を保持し、DB更新成功後に旧画像を整理、失敗時は新規画像を清掃します。入力エラー時は画像を再選択してください。画像がない／不正なパスの場合は「画像なし」を表示します。

## お問い合わせ・メール確認

`/contact`で名前・メール（各255文字以内）・本文（2000文字以内）を入力します。送信成功時は商品一覧へ移動し、失敗時は入力を保持して案内します。問い合わせ保存用DBテーブルはありません。

検索の商品名と問い合わせの名前・本文は、半角英数字・記号、日本語・全角文字と空白を許可し、半角カナ・絵文字は拒否します。問い合わせ本文では改行・タブも使用できます。原資料で未定義の記号は、URLなどを入力できるよう許可しています。

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="Cytech EC"
CONTACT_TO_ADDRESS=admin@example.test
```

宛先は`CONTACT_TO_ADDRESS`（未設定時`admin@example.test`）、差出人はアプリ設定で固定します。入力メールはReply-Toです。Mailpitで宛先・From・Reply-To・件名・本文を確認してください。`MAIL_URL`は未設定とし、空文字の環境変数でSMTP設定を上書きしないでください。実在する管理者への外部配送は今回の範囲外です。

## 購入API・Postman

`POST /api/products/{product_id}/purchases`。`Authorization: Bearer <token>`、`Accept: application/json`、`Content-Type: application/json`を付けて`{"quantity": 1}`を送ります。セッションログインだけではAPIを利用できません。

成功は201で、`data`に`id`（購入記録ID）、`product_id`、`quantity`、`remaining_stock`を返します。401は未認証／無効・期限切れ・失効トークン、403は権限不足／自分の商品、404は存在しない／削除済み商品、422は数量不正、409は在庫不足です。画面とAPIは同じRequest・Modelの購入処理を使用します。

`./vendor/bin/sail artisan tinker`を起動し、実在する購入者ID（以下の`2`を置換）へ1時間限定のトークンを発行します。

```php
$user = App\Models\User::findOrFail(2);
$user->createToken('postman-purchase', ['purchases:create'], now()->addHour())->plainTextToken;
$user->createToken('postman-readonly', ['products:read'], now()->addHour())->plainTextToken;
```

1. `docs/postman/purchase_api.postman_collection.json`と`docs/postman/local.postman_environment.json`をPostmanへImportし、Environmentを選択します。
2. `base_url`と、上記の`token`・`readonly_token`を設定します。実トークンは共有用エクスポートやGitに含めません。
3. `product_id`は他人の在庫2以上の商品、`own_product_id`は本人の商品、`deleted_product_id`は論理削除済み商品、`soldout_product_id`は他人の在庫0の商品を指定します。`missing_product_id`が存在しないことも確認します。
4. 10ケースを実行しTestsを確認します。成功実行ごとに在庫が1減ります。応答IDは`last_sale_id`へ保存されます。
5. phpMyAdminで該当`sales.id`の購入者・商品・数量と`products.stock`を照合し、画面購入と同じ履歴に反映されることを確認します。
6. 次のコマンドで確認用トークンを失効し、再送時の401を確認します。実行結果を[全体確認記録](docs/verification.md)へ追記します。

```php
$user->tokens()->whereIn('name', ['postman-purchase', 'postman-readonly'])->delete();
```

トークン発行画面・APIはありません。期限・権限付きトークンは[Laravel公式Sanctum資料](https://github.com/laravel/docs/blob/13.x/sanctum.md)に沿っています。Postman実機確認は未実施です。既存のHTTP検証だけで確認済みにはしていません。

## テスト・検証

```bash
./vendor/bin/sail php -v
./vendor/bin/sail artisan --version
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test --compact
./vendor/bin/sail npm run build
./vendor/bin/sail composer validate --no-check-publish
./vendor/bin/sail composer check-platform-reqs
```

テストは`phpunit.xml`と`tests/TestCase.php`でMySQLの専用`testing` DBへ制限しています。SailのMySQL初回作成時にテストDBも作成されます。既存ボリュームにない場合は、phpMyAdminで`testing` DBを作り、アプリ用DBユーザーへそのDBの権限を付与してください。開発DBをテストの初期化先にしないでください。

同時購入テストは別PHPプロセスの独立したMySQL接続で最後の1個を競合購入し、1件成功・1件在庫不足を検証します。テスト終了時にテスト用マイグレーションをロールバックするため、その後の手動テストに`testing` DBを使う場合は同DBを明示してマイグレーションしてください。

## 制限・提出物

決済・配送・複数商品のカート・会社管理画面・本番公開は範囲外です。パスワード再設定／変更・メール認証・アカウント削除は公開していません。ページネーションと大量データ対応は未実装です。

購入時の商品情報のスナップショットや、同一要求を一度だけ処理する冪等性キーはありません。成功リクエストを再送すると在庫がある限り別購入になります。画面の多重送信抑止と、在庫整合性を守るトランザクションを実装しています。

`.env`・実トークン・ログ・依存パッケージ本体・ビルド成果物はGit管理外です。仕様書・計画書・画像資料はプロジェクトの親フォルダーにあり、このリポジトリだけを渡す場合は資料も別途同梱してください。コミット・プッシュはユーザーが工程ごとに行います。
