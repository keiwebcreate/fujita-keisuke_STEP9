<footer class="site-footer">
    <div class="site-footer__container">
        <x-action_link :href="route('contact.create')">お問い合わせ</x-action_link>
        <nav class="site-footer__links" aria-label="フッターナビゲーション">
            <a class="text-link" href="{{ route('products.index') }}">Home</a>
            <a class="text-link" href="{{ route('mypage.show') }}" @if (request()->routeIs('mypage.*')) aria-current="page" @endif>マイページ</a>
        </nav>
        <p class="site-footer__copyright">© 2024 Company, Inc</p>
    </div>
</footer>
