<nav class="site-navigation" data-navigation aria-label="{{ __('Main navigation') }}">
    <div class="site-navigation__container">
        <div class="site-navigation__bar">
            <a class="site-navigation__brand" href="{{ route('dashboard') }}"><x-application_logo class="site-navigation__logo" /></a>
            <div class="site-navigation__links">
                <x-nav_link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-nav_link>
            </div>
            <div class="site-navigation__account">
                <x-dropdown>
                    <x-slot name="trigger">
                        <button type="button" class="site-navigation__account-button" aria-expanded="false">
                            {{ Auth::user()->name }} <span aria-hidden="true">⌄</span>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown_link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown_link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown__link">{{ __('Log Out') }}</button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            <button type="button" class="site-navigation__toggle" data-navigation-toggle aria-expanded="false" aria-controls="mobileNavigation">{{ __('Menu') }}</button>
        </div>
    </div>
    <div id="mobileNavigation" class="site-navigation__mobile" data-navigation-panel hidden>
        <x-responsive_nav_link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive_nav_link>
        <div class="site-navigation__identity">
            <div class="site-navigation__name">{{ Auth::user()->name }}</div>
            <div class="site-navigation__email">{{ Auth::user()->email }}</div>
        </div>
        <x-responsive_nav_link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive_nav_link>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="navigation-link navigation-link--mobile">{{ __('Log Out') }}</button>
        </form>
    </div>
</nav>
