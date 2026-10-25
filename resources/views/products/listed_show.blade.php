<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">出品商品詳細</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="出品商品詳細">
                <div class="content-panel__body">
                    @if (session('status'))
                        <p class="status-message status-message--success" role="status">{{ session('status') }}</p>
                    @endif
                    <x-input_error :messages="$errors->get('deletion')" />
                    <x-product_info :product="$product" />
                    <div class="form-actions">
                        <x-action_link :href="route('listed_products.edit', $product->id)">編集</x-action_link>
                        <x-danger_button type="button" data-modal-open="deleteProductModal">削除する</x-danger_button>
                        <x-action_link :href="route('mypage.show')" variant="secondary">戻る</x-action_link>
                    </div>
                    <x-modal name="deleteProductModal" max-width="md">
                        <div class="modal__body">
                            <h2 id="deleteProductModalTitle" class="mypage-section__title">商品の削除確認</h2>
                            <p>「{{ $product->product_name }}」を削除しますか？</p>
                            <p>削除後は一覧から非表示になります。購入履歴は保持されます。</p>
                            <form method="POST" action="{{ route('listed_products.destroy', $product->id) }}">
                                @csrf
                                @method('DELETE')
                                <div class="form-actions">
                                    <x-secondary_button type="button" data-modal-close autofocus>キャンセル</x-secondary_button>
                                    <x-danger_button>削除を確定する</x-danger_button>
                                </div>
                            </form>
                        </div>
                    </x-modal>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
