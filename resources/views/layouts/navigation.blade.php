<nav class="site-navigation" data-navigation aria-label="メインナビゲーション">
    <div class="site-navigation__container">
        <div class="site-navigation__bar">
            <a class="site-navigation__brand" href="{{ route('products.index') }}">{{ config('app.name') }}</a>
            <div class="site-navigation__links">
                <x-nav_link :href="route('products.index')" :active="request()->routeIs('products.index')">Home</x-nav_link>
            </div>
            <div class="site-navigation__account">
                <x-dropdown>
                    <x-slot name="trigger">
                        <button type="button" class="site-navigation__account-button" aria-expanded="false">
                            ログインユーザー：{{ Auth::user()->name }} <span aria-hidden="true">⌄</span>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown__link">ログアウト</button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            <button type="button" class="site-navigation__toggle" data-navigation-toggle aria-expanded="false" aria-controls="mobileNavigation">メニュー</button>
        </div>
    </div>
    <div id="mobileNavigation" class="site-navigation__mobile" data-navigation-panel hidden>
        <x-responsive_nav_link :href="route('products.index')" :active="request()->routeIs('products.index')">Home</x-responsive_nav_link>
        <div class="site-navigation__identity">
            <div class="site-navigation__name">ログインユーザー：{{ Auth::user()->name }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="navigation-link navigation-link--mobile">ログアウト</button>
        </form>
    </div>
</nav>
