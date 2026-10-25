<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">出品商品詳細</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="出品商品詳細">
                <div class="content-panel__body">
                    <x-product_info :product="$product" />
                    <div class="form-actions">
                        <x-primary_button type="button" disabled>編集（準備中）</x-primary_button>
                        <x-danger_button type="button" disabled>削除する（準備中）</x-danger_button>
                        <x-action_link :href="route('mypage.show')" variant="secondary">戻る</x-action_link>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
