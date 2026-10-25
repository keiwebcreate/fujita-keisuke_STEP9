<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">アカウント情報編集</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="アカウント編集">
                <div class="content-panel__body">
                    <x-input_error :messages="$errors->get('account')" />
                    <form method="POST" action="{{ route('account.update') }}">
                        @csrf
                        @method('PATCH')
                        @foreach (['name' => ['ユーザー名', 'accountName', 'text'], 'email' => ['Eメール', 'accountEmail', 'email'], 'name_kanji' => ['名前（漢字）', 'accountNameKanji', 'text'], 'name_kana' => ['名前（カナ）', 'accountNameKana', 'text']] as $field => [$label, $id, $type])
                            <div class="form-field">
                                <x-input_label :for="$id" :value="$label.'（必須）'" />
                                <x-text_input :id="$id" :name="$field" :type="$type" :value="is_scalar(old($field, $user->$field)) ? old($field, $user->$field) : ''" required maxlength="255" :aria-describedby="$id.'Errors'" />
                                <x-input_error :id="$id.'Errors'" :messages="$errors->get($field)" />
                            </div>
                        @endforeach
                        <div class="form-actions">
                            <x-action_link :href="route('mypage.show')" variant="secondary">戻る</x-action_link>
                            <x-primary_button>更新</x-primary_button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
