<x-guest-layout>
    <!-- Session Status -->
    <x-auth_session_status class="auth-page__status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input_label for="email" :value="__('Email')" />
            <x-text_input id="email" class="form-field__input" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input_error :messages="$errors->get('email')" class="form-field__errors" />
        </div>

        <!-- Password -->
        <div class="form-field">
            <x-input_label for="password" :value="__('Password')" />

            <x-text_input id="password" class="form-field__input"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input_error :messages="$errors->get('password')" class="form-field__errors" />
        </div>

        <!-- Remember Me -->
        <div class="auth-form__remember">
            <label for="rememberMe" class="checkbox-field">
                <input id="rememberMe" type="checkbox" class="checkbox-field__input" name="remember">
                <span class="checkbox-field__label">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="form-actions form-actions--end">
            @if (Route::has('password.request'))
                <a class="text-link" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary_button class="form-actions__button">
                {{ __('Log in') }}
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
