<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">出品商品編集</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="出品商品編集">
                <div class="content-panel__body">
                    <x-input_error :messages="$errors->get('update')" />
                    <form method="POST" action="{{ route('listed_products.update', $product->id) }}" enctype="multipart/form-data" class="product-form">
                        @csrf
                        @method('PATCH')
                        <x-product_form_fields :product="$product" :image-required="false" />
                        <div class="form-actions">
                            <x-action_link :href="route('listed_products.show', $product->id)" variant="secondary">戻る</x-action_link>
                            <x-primary_button>更新</x-primary_button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
