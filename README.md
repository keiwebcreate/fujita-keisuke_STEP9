# Cytech EC — 開発環境・DB基盤・商品閲覧

実装先：`/home/keisuke/CyTech_STEP9/cytech_STEP9_app`

## 構成

- PHP 8.5.12 / Laravel 13 / Laravel Sail
- Laravel Breeze 2.4.2（Blade・PHPUnit）
- Vite 8 / BEM形式の外部CSS / 外部JavaScript（メニュー・モーダル等）
- MySQL 8.4 / phpMyAdmin / Mailpit
- Laravel Boost（AGENTS.mdの指定に従った開発用パッケージ）

現在はSTEP 08まで実装済みです。登録後は自動ログインせずログイン画面へ、ログイン後は商品一覧へ、ログアウト後はログイン画面へ遷移します。商品一覧・詳細・マイページ・共通ヘッダー／フッターが利用できます。商品登録・本人限定の出品詳細・編集・論理削除が利用できます。商品名・価格による非同期検索も利用できます。お気に入りはSTEP 09、購入はSTEP 10で実装します。

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
| ログイン | http://localhost:8000/login |
| ユーザー登録 | http://localhost:8000/register |
| 商品一覧（ログイン必須） | http://localhost:8000/products |
| マイページ（ログイン必須） | http://localhost:8000/mypage |
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
- STEP 02時点では登録後の遷移を維持していたが、STEP 03で下記の仕様へ変更済み。

## STEP 03：認証と画面遷移（2026-10-25）

| 操作 | 結果 |
|---|---|
| ユーザー登録 | 漢字名・カナ名等を保存し、自動ログインせずログイン画面へ。完了メッセージを表示 |
| ログイン | 商品一覧`/products`へ。ログイン前のアクセス先があっても商品一覧を優先 |
| ログアウト | POSTで認証・セッションを破棄し、CSRFトークンを再生成してログイン画面へ |
| 未ログインで商品一覧へアクセス | ログイン画面へ |
| ログイン済みでログイン・登録画面へアクセス | 商品一覧へ |
| `/`へアクセス | 未ログインならログイン画面、ログイン済みなら商品一覧へ |

ユーザー回答により、ログイン・新規登録・ログアウトだけを採用しています。ログイン保持、パスワード再設定、メール認証、パスワード確認・変更、アカウント削除は公開していません。旧`/dashboard`・`/profile`も廃止しています。プロフィール編集機能はSTEP 12で仕様に合わせて実装します。

不要機能のController・Request・View・Modelメソッドと関連テストを整理しました。既存DBの`remember_token`・`email_verified_at`・`password_reset_tokens`等は保持していますが、これらを使う付属機能は有効にしていません。Mailpitは引き続き利用でき、お問い合わせメールはSTEP 13で実装します。

登録時のパスワードは半角英数字8〜72文字・確認一致です。入力エラー時は安全な入力値を保持し、パスワードと確認用パスワードを再表示しません。入力検証はRequest、保存はUserモデル、スタイルと画面操作は外部CSS・JavaScriptで管理しています。

確認結果：全67テスト（334 assertions）・Pint・ビルドが成功。登録後の未認証状態、パスワードのハッシュ化、重複メール・不正入力、ログイン試行制限と時間経過後の解除、成功時の失敗回数リセット、セッションID・CSRFトークン更新、CSRFなしの送信拒否、認証保護、廃止ルートの404応答を確認しています。

専用テストDBを使ったChromium操作でも、登録エラー後の入力保持・パスワード再表示の防止、登録後に自動ログインしないこと、ログイン後の商品一覧表示、モバイルのログアウトと再アクセス時の認証保護を確認しました。開発DBのユーザーデータは変更していません。コミット・プッシュはユーザーが行います。

## STEP 04：共通画面部品・商品一覧・商品詳細（2026-10-25）

- `/products`で自分以外の未削除商品を商品番号の昇順で表示。列は商品番号・商品名・商品説明・画像・料金・詳細リンク。商品がなければ空一覧のメッセージを表示します。
- `/products/{product_id}`で商品名・説明・画像・金額・商品に設定された会社名を表示。一覧へ戻る導線があります。
- ユーザー回答により、自分の商品詳細の直接閲覧も許可しています。存在しない／削除済みの商品は404です。購入の可否とは別の扱いです。
- 一覧・詳細のデータ取得はProductモデルへ集約。ヘッダー・フッター・商品情報・画像・料金・操作リンクをBladeコンポーネントにしています。
- スマートフォンでは表の領域内を横スクロールでき、メニューでHome・ログアウト等を表示します。

マイページ・お問い合わせへの導線は準備中です。商品詳細のお気に入り・カート操作も無効にしており、対応STEPで有効化します。検索はまだ実装していません。フッターの著作権表記は原資料の例を使用しています。

### 商品画像

`products.img_path`には公開ディスクからの相対パスを保存します。例：`products/tablet.png`。実際のファイルは`storage/app/public/products/tablet.png`へ配置し、`public/storage`経由で表示します。

```bash
./vendor/bin/sail artisan storage:link
```

現在の環境ではリンク作成済みです。新しい環境でも上記コマンドを実行してください。商品画像のアップロードはSTEP 06で実装します。表示対象は`products/`配下の英数字・ハイフン・アンダースコアによるファイルパスと、JPEG・PNG・WebP形式です。ファイルがない場合や、外部URL・親ディレクトリへの参照などの不正なパスでは「画像なし」を表示します。

### 確認結果

全85テスト（404 assertions）・Pint・ビルドに成功しました。専用テストDBのChromium操作でも、一覧の表示対象・順序、公開画像の読み込み、商品詳細の会社・金額、戻る操作、無効な操作、自分の商品詳細、404、モバイルの表の横スクロール・メニューを確認しています。

開発DBへサンプル商品は投入していません。開発DBに商品がない場合は空一覧になります。ブラウザ確認用のデータ・画像は削除済みで、一時サーバーも停止しました。コミット・プッシュはユーザーが行います。

## STEP 05：マイページ（2026-10-25）

`/mypage`に、ログインユーザーの情報・出品一覧・購入一覧を表示します。ヘッダー・フッター・モバイルメニューのマイページリンクから移動できます。

- ユーザー情報：ユーザー名・Eメール・漢字名・カナ名。既存データのカナ名がNULLの場合は「未設定」と表示。
- 出品一覧：本人の未削除商品だけを商品番号の昇順で表示。列は商品番号・商品名・商品説明・料金・詳細操作。
- 購入一覧：本人の購入記録だけを購入日時の昇順、同日時なら購入IDの昇順で表示。列は商品名・商品説明・現在の料金・購入個数。
- 商品が更新・論理削除されても購入履歴は参照可能。購入時点の情報保存用カラムは設けず、現在の商品情報を表示。
- 未出品・未購入の場合は、それぞれ空一覧のメッセージを表示。会社未設定でも閲覧可能。

DB取得はUserモデルへ集約し、購入商品の関連を一括取得しています。URLのパラメータで他人の情報に切り替えることはできません。商品登録・出品詳細はSTEP 06、アカウント編集はSTEP 12まで準備中の無効表示です。

全95テスト（456 assertions）・Pint・ビルドに成功。専用テストDBのChromium操作でも、表示内容・削除済み商品の購入履歴・価格と数量・空一覧・導線・無効操作・モバイル表示を確認しました。表はカード内で横スクロールし、ページ全体の横幅が広がらないことも確認しています。

開発DBのユーザー・商品・購入データは変更していません。コミット・プッシュはユーザーが行います。


## STEP 06：商品新規登録・出品商品詳細（2026-10-25）

- マイページの新規登録から商品登録画面（`GET /products/create`）へ移動できます。商品名・価格・商品説明・在庫数・画像を入力し、登録後は完了メッセージとともにマイページへ戻ります。
- 商品名・説明は255文字以内の半角英数字・全角文字・空白を許可し、説明は改行にも対応します。価格・在庫数は0〜2147483647の半角数字の整数です。
- 画像はJPEG・PNG・WebP、最大2MiB。生成したファイル名で公開ストレージの`products/`へ保存します。入力エラー時は画像を再選択してください。DB登録失敗時は保存済み画像を清掃し、安全な入力を保持します。
- 出品者と会社は認証ユーザーから設定し、フォームで送信されたID・画像パスは採用しません。会社未設定の場合は理由を表示し、サーバー側でも登録を拒否します。会社設定はSTEP 02のphpMyAdmin手順に従ってください。
- マイページの出品詳細から`GET /mypage/products/{product_id}`へ移動できます。本人以外は403、存在しない／削除済み商品は404です。編集・削除はSTEP 07まで無効表示です。
- 画像資料に合わせた縦並びの登録フォーム、出品詳細の情報・画像・操作配置を採用。入力部品は編集でも再利用できるBladeコンポーネントに集約しています。

確認結果：全120テスト（627 assertions）・Pint・ビルドに成功。専用testing DBでのChromium操作により、登録・エラー後の入力保持・画像読み込み・本人以外の403・会社未設定・PC／モバイル表示を確認しました。開発DBは変更していません。


## STEP 07：出品商品の編集・論理削除（2026-10-25）

- 出品詳細の「編集」から編集画面へ移動できます。商品名・価格・説明・在庫の現在値と既存画像を表示し、更新後は出品詳細に戻ります。画像資料に合わせ、既存画像とファイル選択欄を横に配置しています。
- 画像未選択なら既存画像を保持。新規画像の条件は登録時と同じです。DB更新成功後に旧画像を整理し、DB更新失敗時は新規画像を清掃して既存データ・画像を保持します。旧画像の清掃だけが失敗した場合は更新成功を維持し、ログに記録します。
- 更新対象は商品名・価格・説明・在庫・選択した画像のみです。出品者・会社は変更できません。出品後にユーザーの所属会社が未設定になっても、本人の既存商品は編集できます。
- 削除は確認モーダルで「削除を確定する」を押すと実行し、マイページへ戻ります。キャンセル・Escでは処理しません。商品は論理削除し、一覧から除外します。画像・購入記録・お気に入りの関連行は保持します。
- 編集・更新・削除は本人のみ操作可能。他人は403、存在しない／削除済み商品は404です。Model内で商品行をロックして更新・削除を行い、DB処理失敗時はロールバックします。

確認結果：全147テスト（809 assertions）・Pint・ビルドに成功。専用testing DBのChromium操作で編集・入力保持・画像未変更／差し替え・削除キャンセル／Esc／確定・削除後の購入履歴・PC／モバイル表示を確認しました。開発DBは変更していません。


## STEP 08：商品名・価格の非同期検索（2026-10-25）

- 商品一覧上部に商品名・最低価格・最高価格・検索ボタンを配置。商品名は部分一致、条件はAND、価格の上下限を含みます。片側指定が可能で、空条件は自分以外の未削除商品を全件表示します。商品番号の昇順を維持します。
- 商品名は255文字以内、価格は0〜2147483647の半角数字の整数です。最低価格が最高価格を超える場合はエラーを表示します。商品名に含まれる`%`・`_`は検索ワイルドカードとして展開しません。
- `GET /products`へJSONを要求し、`html`と`count`を受け取って一覧部分だけを更新します。初期表示と検索結果は同じBladeを使用し、通常のGET表示でも同じ条件で検索できます。
- 検索中・件数・0件・入力エラー・通信失敗・認証切れを表示します。失敗時は前回結果を保持。再検索と条件変更では古い通信を中断し、要求番号も照合して古い応答の反映を防ぎます。
- 全168テスト（908 assertions）・Pint・ビルドに成功。専用testing DBでChromiumによる非同期更新、入力エラー、通信失敗、応答順序逆転、PC／モバイル表示、詳細への遷移を確認しました。開発DBは変更していません。
