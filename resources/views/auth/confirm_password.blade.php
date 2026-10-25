<x-guest-layout>
    <div class="auth-page__description">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input_label for="password" :value="__('Password')" />

            <x-text_input id="password" class="form-field__input"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input_error :messages="$errors->get('password')" class="form-field__errors" />
        </div>

        <div class="form-actions form-actions--end">
            <x-primary_button>
                {{ __('Confirm') }}
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
