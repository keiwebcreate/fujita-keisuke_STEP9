@props(['userName'])
<nav class="site-navigation" data-navigation aria-label="メインナビゲーション">
    <div class="site-navigation__container">
        <div class="site-navigation__bar">
            <a class="site-navigation__brand" href="{{ route('products.index') }}">{{ config('app.name') }}</a>
            <div class="site-navigation__links">
                <x-nav_link :href="route('products.index')" :active="request()->routeIs('products.*')">Home</x-nav_link>
                <x-nav_link :href="route('mypage.show')" :active="request()->routeIs('mypage.*')">マイページ</x-nav_link>
            </div>
            <div class="site-navigation__account">
                <span class="site-navigation__user">ログインユーザー：{{ $userName }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-danger_button>ログアウト</x-danger_button>
                </form>
            </div>
            <button type="button" class="site-navigation__toggle" data-navigation-toggle aria-expanded="false" aria-controls="mobileNavigation">メニュー</button>
        </div>
    </div>
    <div id="mobileNavigation" class="site-navigation__mobile" data-navigation-panel hidden>
        <x-responsive_nav_link :href="route('products.index')" :active="request()->routeIs('products.*')">Home</x-responsive_nav_link>
        <x-responsive_nav_link :href="route('mypage.show')" :active="request()->routeIs('mypage.*')">マイページ</x-responsive_nav_link>
        <div class="site-navigation__identity">
            <div class="site-navigation__name">ログインユーザー：{{ $userName }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="site-navigation__logout">
            @csrf
            <x-danger_button>ログアウト</x-danger_button>
        </form>
    </div>
</nav>
