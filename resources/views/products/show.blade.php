<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">商品詳細</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="商品詳細">
                <div class="content-panel__body">
                    <x-product_info :product="$product" show-company />
                    @if ($product->stock === 0)
                        <p class="stock-status">売り切れ</p>
                    @endif
                    <p class="product-detail__notice">お気に入り・購入機能は準備中です。</p>
                    <button type="button" class="button button--favorite" disabled>お気に入り（準備中）</button>
                    <div class="form-actions">
                        <x-primary_button type="button" disabled>カートに追加する</x-primary_button>
                        <x-action_link :href="route('products.index')" variant="secondary">戻る</x-action_link>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
