<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">お問い合わせフォーム</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="お問い合わせ">
                <div class="content-panel__body">
                    <x-input_error :messages="$errors->get('sending')" />
                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf
                        <div class="form-field">
                            <x-input_label for="contactName" value="名前（必須）" />
                            <x-text_input id="contactName" name="name" type="text" :value="is_scalar(old('name', '')) ? old('name', '') : ''" required maxlength="255" autocomplete="name" />
                            <x-input_error :messages="$errors->get('name')" />
                        </div>
                        <div class="form-field">
                            <x-input_label for="contactEmail" value="メールアドレス（必須）" />
                            <x-text_input id="contactEmail" name="email" type="email" :value="is_scalar(old('email', '')) ? old('email', '') : ''" required maxlength="255" autocomplete="email" />
                            <x-input_error :messages="$errors->get('email')" />
                        </div>
                        <div class="form-field">
                            <x-input_label for="contactMessage" value="お問い合わせ内容（必須）" />
                            <textarea id="contactMessage" name="message" class="form-field__input" rows="6" required maxlength="2000">{{ is_scalar(old('message', '')) ? old('message', '') : '' }}</textarea>
                            <x-input_error :messages="$errors->get('message')" />
                        </div>
                        <div class="form-actions">
                            <x-primary_button>送信</x-primary_button>
                            <x-action_link :href="route('products.index')" variant="secondary">戻る</x-action_link>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
