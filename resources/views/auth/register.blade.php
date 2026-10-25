<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input_label for="name" :value="__('Name')" />
            <x-text_input id="name" class="form-field__input" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input_error :messages="$errors->get('name')" class="form-field__errors" />
        </div>

        <!-- Email Address -->
        <div class="form-field">
            <x-input_label for="email" :value="__('Email')" />
            <x-text_input id="email" class="form-field__input" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input_error :messages="$errors->get('email')" class="form-field__errors" />
        </div>

        <!-- Password -->
        <div class="form-field">
            <x-input_label for="password" :value="__('Password')" />

            <x-text_input id="password" class="form-field__input"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input_error :messages="$errors->get('password')" class="form-field__errors" />
        </div>

        <!-- Confirm Password -->
        <div class="form-field">
            <x-input_label for="passwordConfirmation" :value="__('Confirm Password')" />

            <x-text_input id="passwordConfirmation" class="form-field__input"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input_error :messages="$errors->get('password_confirmation')" class="form-field__errors" />
        </div>

        <div class="form-actions form-actions--end">
            <a class="text-link" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary_button class="form-actions__button">
                {{ __('Register') }}
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
