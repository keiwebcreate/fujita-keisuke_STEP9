{{-- ログイン用フォームです。入力を送るとLoginRequestで確認され、認証処理へ進みます。 --}}
<x-guest-layout>
    <h1 class="auth-page__title">ログイン</h1>
    <x-auth_session_status class="auth-page__status" :status="session('status')" />
    <form class="auth-form" method="POST" action="{{ route('login') }}">
        {{-- csrfは、他のサイトから勝手に送信されるのを防ぐ確認用の値を埋め込みます。 --}}
        @csrf
        <div>
            <x-input_label for="email" value="メールアドレス" />
            <x-text_input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input_error :messages="$errors->get('email')" />
        </div>
        <div class="form-field">
            <x-input_label for="password" value="パスワード" />
            <x-text_input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input_error :messages="$errors->get('password')" />
        </div>
        <div class="form-actions form-actions--between">
            <a class="text-link" href="{{ route('register') }}">新規ユーザー登録</a>
            <x-primary_button>ログイン</x-primary_button>
        </div>
    </form>
</x-guest-layout>
