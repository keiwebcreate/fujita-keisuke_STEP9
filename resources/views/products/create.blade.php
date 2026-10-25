{{-- 商品登録画面です。共通の入力部品を使い、画像も送れる形式で送信します。 --}}
<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">商品登録</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="商品新規登録">
                <div class="content-panel__body">
                    @unless ($can_list)
                        <p class="product-form__notice" role="alert">所属会社が未設定のため商品を登録できません。管理者に会社の設定を依頼してください。</p>
                    @endunless
                    <x-input_error :messages="$errors->get('company')" />
                    <x-input_error :messages="$errors->get('registration')" />
                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="product-form">
                        {{-- csrfは、他のサイトから勝手に送信されるのを防ぐ確認用の値を埋め込みます。 --}}
                        @csrf
                        <x-product_form_fields />
                        <div class="form-actions">
                            <x-action_link :href="route('mypage.show')" variant="secondary">戻る</x-action_link>
                            <x-primary_button :disabled="! $can_list">登録</x-primary_button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
