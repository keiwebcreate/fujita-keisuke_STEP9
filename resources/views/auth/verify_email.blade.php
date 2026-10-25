<x-guest-layout>
    <div class="auth-page__description">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="status-message status-message--success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="form-actions form-actions--between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary_button>
                    {{ __('Resend Verification Email') }}
                </x-primary_button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-link">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
