<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input_label for="email" :value="__('Email')" />
            <x-text_input id="email" class="form-field__input" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input_error :messages="$errors->get('email')" class="form-field__errors" />
        </div>

        <!-- Password -->
        <div class="form-field">
            <x-input_label for="password" :value="__('Password')" />
            <x-text_input id="password" class="form-field__input" type="password" name="password" required autocomplete="new-password" />
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
            <x-primary_button>
                {{ __('Reset Password') }}
            </x-primary_button>
        </div>
    </form>
</x-guest-layout>
