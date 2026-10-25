<x-guest-layout>
    <h1 class="auth-page__title">新規ユーザー登録</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input_label for="name" value="ユーザー名" />
            <x-text_input id="name" class="form-field__input" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input_error :messages="$errors->get('name')" class="form-field__errors" />
        </div>

        <div class="form-field">
            <x-input_label for="nameKanji" :value="__('名前（漢字）')" />
            <x-text_input id="nameKanji" type="text" name="name_kanji" :value="old('name_kanji')" required maxlength="255" />
            <x-input_error :messages="$errors->get('name_kanji')" />
        </div>

        <div class="form-field">
            <x-input_label for="nameKana" :value="__('名前（カナ）')" />
            <x-text_input id="nameKana" type="text" name="name_kana" :value="old('name_kana')" required maxlength="255" />
            <x-input_error :messages="$errors->get('name_kana')" />
        </div>

        <!-- Email Address -->
        <div class="form-field">
            <x-input_label for="email" value="メールアドレス" />
            <x-text_input id="email" class="form-field__input" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input_error :messages="$errors->get('email')" class="form-field__errors" />
        </div>

        <!-- Password -->
        <div class="form-field">
            <x-input_label for="password" value="パスワード" />

            <x-text_input id="password" class="form-field__input"
                            type="password"
                            name="password"
                            minlength="8" maxlength="72" required autocomplete="new-password" />

            <x-input_error :messages="$errors->get('password')" class="form-field__errors" />
        </div>

        <!-- Confirm Password -->
        <div class="form-field">
            <x-input_label for="passwordConfirmation" value="パスワード（確認用）" />

            <x-text_input id="passwordConfirmation" class="form-field__input"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input_error :messages="$errors->get('password_confirmation')" class="form-field__errors" />
        </div>

        <div class="form-actions form-actions--end">
            <a class="text-link" href="{{ route('login') }}">
                戻る
            </a>

            <x-primary_button class="form-actions__button">
                登録
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
