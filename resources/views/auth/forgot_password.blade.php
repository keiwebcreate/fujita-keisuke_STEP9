<x-guest-layout>
    <div class="auth-page__description">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth_session_status class="auth-page__status" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input_label for="email" :value="__('Email')" />
            <x-text_input id="email" class="form-field__input" type="email" name="email" :value="old('email')" required autofocus />
            <x-input_error :messages="$errors->get('email')" class="form-field__errors" />
        </div>

        <div class="form-actions form-actions--end">
            <x-primary_button>
                {{ __('Email Password Reset Link') }}
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
