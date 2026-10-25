{{-- メール本文です。二重の波かっこは入力中のHTML記号を安全な文字に変え、タグとして実行させません。 --}}
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="utf-8"><title>お問い合わせ</title></head>
<body>
    <h1>Cytech EC お問い合わせ</h1>
    <p>名前：{{ $contactName }}</p>
    <p>メールアドレス：{{ $contactEmail }}</p>
    <h2>お問い合わせ内容</h2>
    <pre>{{ $contactBody }}</pre>
</body>
</html>
