{{-- 購入する数量を入力する画面です。data-purchase-*をJavaScriptが読み、確認と送信を制御します。 --}}
<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">購入画面</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="商品購入">
                <div class="content-panel__body">
                    <x-input_error :messages="$errors->get('purchase')" />
                    @if ($product->stock === 0)
                        <p class="stock-status" role="status">売り切れのため購入できません。</p>
                    @endif
                    <form id="purchaseForm" method="POST" action="{{ route('purchases.store', $product->id) }}" data-purchase-form>
                        {{-- csrfは、他のサイトから勝手に送信されるのを防ぐ確認用の値を埋め込みます。 --}}
                        @csrf
                        <x-product_info :product="$product" show-stock show-company>
                            <x-slot name="quantity">
                                <x-input_label for="purchaseQuantity" value="購入数量（必須）" class="form-field__label--visually-hidden" />
                                <x-text_input id="purchaseQuantity" name="quantity" type="text" inputmode="numeric" pattern="[0-9]+" :value="is_scalar(old('quantity', '1')) ? old('quantity', '1') : '1'" required maxlength="10" class="purchase-form__quantity" aria-describedby="purchaseQuantityHelp" />
                                <p id="purchaseQuantityHelp" class="product-form__help">数量は1以上の整数で入力してください。</p>
                                <x-input_error :messages="$errors->get('quantity')" />
                            </x-slot>
                        </x-product_info>
                        <div class="form-actions">
                            <x-primary_button :disabled="$product->stock === 0" data-purchase-open aria-controls="purchaseConfirmModal" aria-haspopup="dialog">購入する</x-primary_button>
                            <x-action_link :href="route('products.show', $product->id)" variant="secondary">戻る</x-action_link>
                        </div>
                    </form>
                    <x-modal name="purchaseConfirmModal" max-width="md">
                        <div class="modal__body">
                            <h2 id="purchaseConfirmModalTitle" class="mypage-section__title">購入内容の確認</h2>
                            <p>「{{ $product->product_name }}」を<span data-purchase-quantity></span>個購入しますか？</p>
                            <p>単価：<x-product_price :price="$product->price" /></p>
                            <div class="form-actions">
                                <x-secondary_button type="button" data-modal-close autofocus>キャンセル</x-secondary_button>
                                <x-primary_button type="button" data-purchase-confirm>購入を確定する</x-primary_button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
