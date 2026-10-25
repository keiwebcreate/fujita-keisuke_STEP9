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
                    <div class="product-favorite" data-product-favorite>
                        <button type="button" @class(['favorite-button', 'favorite-button--active' => $liked]) data-favorite-button data-favorite-url="{{ route('products.like.store', $product->id) }}" aria-pressed="{{ $liked ? 'true' : 'false' }}" aria-label="{{ $liked ? 'お気に入りを解除' : 'お気に入りに登録' }}" aria-describedby="favoriteStatus" aria-busy="false">
                            <svg class="favorite-button__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21 3.4 12.6C-2 7.2 5.8-1 12 5.2 18.2-1 26 7.2 20.6 12.6Z" /></svg>
                        </button>
                        <span id="favoriteStatus" class="product-favorite__status" data-favorite-status role="status" aria-live="polite">{{ $liked ? 'お気に入り登録済み' : 'お気に入り未登録' }}</span>
                        <p class="product-favorite__error" data-favorite-error role="alert" hidden></p>
                    </div>
                    @if ($product->user_id === auth()->id())
                        <p class="product-detail__notice">自分の商品は購入できません。</p>
                    @endif
                    <div class="form-actions">
                        @if ($product->user_id !== auth()->id() && $product->stock > 0)
                            <x-action_link :href="route('purchases.create', $product->id)">カートに追加する</x-action_link>
                        @else
                            <x-primary_button type="button" disabled>カートに追加する</x-primary_button>
                        @endif
                        <x-action_link :href="route('products.index')" variant="secondary">戻る</x-action_link>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
