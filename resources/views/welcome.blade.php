<x-guest-layout>
    <section class="welcome-page">
        <h1 class="welcome-page__title">{{ config('app.name') }}</h1>
        <p class="welcome-page__description">{{ __('Welcome') }}</p>
        <div class="form-actions">
            @auth
                <a class="button button--primary" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            @else
                <a class="button button--primary" href="{{ route('login') }}">{{ __('Log in') }}</a>
                <a class="button button--secondary" href="{{ route('register') }}">{{ __('Register') }}</a>
            @endauth
        </div>
    </section>
</x-guest-layout>
