<section>
    <header>
        <h2 class="profile-section__title">
            {{ __('Update Password') }}
        </h2>

        <p class="profile-section__description">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-form">
        @csrf
        @method('put')

        <div>
            <x-input_label for="updatePasswordCurrentPassword" :value="__('Current Password')" />
            <x-text_input id="updatePasswordCurrentPassword" name="current_password" type="password" class="form-field__input" autocomplete="current-password" />
            <x-input_error :messages="$errors->updatePassword->get('current_password')" class="form-field__errors" />
        </div>

        <div>
            <x-input_label for="updatePassword" :value="__('New Password')" />
            <x-text_input id="updatePassword" name="password" type="password" class="form-field__input" autocomplete="new-password" />
            <x-input_error :messages="$errors->updatePassword->get('password')" class="form-field__errors" />
        </div>

        <div>
            <x-input_label for="updatePasswordConfirmation" :value="__('Confirm Password')" />
            <x-text_input id="updatePasswordConfirmation" name="password_confirmation" type="password" class="form-field__input" autocomplete="new-password" />
            <x-input_error :messages="$errors->updatePassword->get('password_confirmation')" class="form-field__errors" />
        </div>

        <div class="form-actions">
            <x-primary_button>{{ __('Save') }}</x-primary_button>

            @if (session('status') === 'password-updated')
                <p
                    data-auto-dismiss="2000"
                    class="status-message"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
