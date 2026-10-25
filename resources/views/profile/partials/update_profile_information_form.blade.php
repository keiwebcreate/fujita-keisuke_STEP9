<section>
    <header>
        <h2 class="profile-section__title">
            {{ __('Profile Information') }}
        </h2>

        <p class="profile-section__description">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="sendVerification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-form">
        @csrf
        @method('patch')

        <div>
            <x-input_label for="name" :value="__('Name')" />
            <x-text_input id="name" name="name" type="text" class="form-field__input" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input_error class="form-field__errors" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input_label for="email" :value="__('Email')" />
            <x-text_input id="email" name="email" type="email" class="form-field__input" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input_error class="form-field__errors" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="profile-section__notice">
                        {{ __('Your email address is unverified.') }}

                        <button type="submit" form="sendVerification" class="text-link">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="status-message status-message--success">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="form-actions">
            <x-primary_button>{{ __('Save') }}</x-primary_button>

            @if (session('status') === 'profile-updated')
                <p
                    data-auto-dismiss="2000"
                    class="status-message"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
