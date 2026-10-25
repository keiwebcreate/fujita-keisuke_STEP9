<section class="profile-section">
    <header>
        <h2 class="profile-section__title">
            {{ __('Delete Account') }}
        </h2>

        <p class="profile-section__description">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger_button
        type="button"
        data-modal-open="confirmUserDeletion"
    >{{ __('Delete Account') }}</x-danger_button>

    <x-modal name="confirmUserDeletion" :show="$errors->userDeletion->isNotEmpty()">
        <form method="post" action="{{ route('profile.destroy') }}" class="modal__body">
            @csrf
            @method('delete')

            <h2 id="confirmUserDeletionTitle" class="profile-section__title">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="profile-section__description">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="form-field">
                <x-input_label for="deleteAccountPassword" value="{{ __('Password') }}" class="form-field__label form-field__label--visually-hidden" />

                <x-text_input
                    id="deleteAccountPassword"
                    name="password"
                    type="password"
                    class="form-field__input form-field__input--compact"
                    placeholder="{{ __('Password') }}"
                />

                <x-input_error :messages="$errors->userDeletion->get('password')" class="form-field__errors" />
            </div>

            <div class="form-actions form-actions--end">
                <x-secondary_button data-modal-close>
                    {{ __('Cancel') }}
                </x-secondary_button>

                <x-danger_button class="form-actions__button">
                    {{ __('Delete Account') }}
                </x-danger_button>
            </div>
        </form>
    </x-modal>
</section>
